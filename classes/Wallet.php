<?php namespace Octobro\Wallet\Classes;

use Db;
use Event;
use Exception;
use Carbon\Carbon;
use ApplicationException;
use Responsiv\Pay\Models\InvoiceItem;
use Octobro\Wallet\Models\Log as WalletLog;
use Octobro\Wallet\Models\Usage;

class Wallet
{
    static function getAvailableBalance($owner)
    {
        $onHoldWalletAmount = Usage::whereOwnerType(get_class($owner))
            ->whereOwnerId($owner->id)
            ->whereStatus(Usage::STATUS_HOLD)
            ->sum('amount');

        return $owner->wallet_amount - $onHoldWalletAmount;
    }

    static function deposit($owner, $ownerName, $invoice, $amount, $description = null)
    {
        $walletLog = WalletLog::createLog($invoice, $owner, $ownerName, $amount, null, $description);

        /**
         * Extensibility
         */
        if (Event::fire('octobro.wallet.afterDeposit', [$invoice, $amount], true) === false) {
            return false;
        }

        return $walletLog;
    }

    static function hold($owner, $invoice, $amount = null)
    {
        if (!$owner) {
            throw new ApplicationException('Owner not found.');
        }

        $availableBalance = self::getAvailableBalance($owner);

        if ($amount == null) {
            // By default, it will use all the wallet amount
            $amount = min($invoice->total, $availableBalance);
        }

        if (!$availableBalance || $availableBalance < $amount) {
            throw new ApplicationException('Insufficient balance.');
        }

        try {
            Db::beginTransaction();
            $walletUsage = new Usage();
            $walletUsage->owner = $owner;
            $walletUsage->amount = $amount;
            $walletUsage->save();

            $invoiceItem = new InvoiceItem([
                'invoice_id'  => $invoice->id,
                'description' => 'Wallet Usage',
                'quantity'    => 1,
                'price'       => -$amount,
                'related'     => $walletUsage,
            ]);
    
            $invoice->items()->save($invoiceItem);
    
            $invoice->is_use_wallet = true;
            $invoice->save();
    
            /**
             * Extensibility
             */
            if (Event::fire('octobro.wallet.afterUseWallet', [$invoice, $amount], true) === false) {
                return false;
            }

            Db::commit();
        }
        catch (Exception $ex) {
            Db::rollBack();
            throw $ex;
        }

        return $walletUsage;
    }

    static function getWalletUsageInvoiceItem($invoice)
    {
        return $invoice->items()->whereRelatedType('Octobro\Wallet\Models\Usage')->first();
    }

    static function use($invoice)
    {
        $walletUsageItem = static::getWalletUsageInvoiceItem($invoice);

        if (!$walletUsageItem) return;

        $walletUsage = $walletUsageItem->related;

        $walletUsage->status  = Usage::STATUS_USED;
        $walletUsage->used_at = Carbon::now();
        $walletUsage->save();

        WalletLog::createLog($invoice, $walletUsage->owner, null, -$walletUsage->amount, null, 'Wallet Usage for Invoice #' . $invoice->id);
    }

    static function remove($invoice)
    {
        $walletUsageItem = static::getWalletUsageInvoiceItem($invoice);

        if (!$walletUsageItem) return;

        $walletUsageItem->related->delete();
        $walletUsageItem->delete();

        $invoice->is_use_wallet = false;
        $invoice->save();
    }
}