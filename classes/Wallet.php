<?php namespace Octobro\Wallet\Classes;

use Event;
use ApplicationException;
use Responsiv\Pay\Models\InvoiceItem;
use Octobro\Wallet\Models\Log as WalletLog;

class Wallet
{
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

    static function use($owner, $ownerName, $invoice, $amount, $cashbackPercentage = null)
    {
        if (!$owner) {
            throw new ApplicationException('Owner not found.');
        }

        if (!$amount) {
            // By default, it will use all the wallet amount
            $amount = min($invoice->total, $owner->wallet_amount);
        }

        if (!$amount) return;

        if ($owner->wallet_amount < $amount) {
            throw new ApplicationException('Insufficient balance.');
        }

        $walletLog = WalletLog::createLog($invoice, $owner, $ownerName, (- $amount), null, 'Wallet usage for Invoice #' . $invoice->id);
        
        $invoiceItem = new InvoiceItem([
            'invoice_id'  => $invoice->id,
            'description' => 'Wallet Usage',
            'quantity'    => 1,
            'price'       => -$amount,
            'related'     => $walletLog,
        ]);

        $invoice->items()->save($invoiceItem);
        $invoice->touchTotals();

        if ($invoice->total == 0 && $invoice->markAsPaymentProcessed()) {
            $invoice->updateInvoiceStatus('paid');
        }


        /**
         * Extensibility
         */
        if (Event::fire('octobro.wallet.afterUseWallet', [$invoice, $amount], true) === false) {
            return false;
        }

        return $walletLog;
    }

    static function remove($owner, $ownerName, $invoice)
    {
        $amount = (-$invoice->items()->where('description', 'Wallet Usage')->first()->total);

        $walletLog = WalletLog::createLog($invoice, $owner, $ownerName, $amount, null, 'Cancel wallet usage for Invoice #' . $invoice->id);
        $invoice->items()->where('description', 'Wallet Usage')->delete();
        $invoice->touchTotals();

        /**
         * Extensibility
         */
        if (Event::fire('octobro.wallet.afterUseWallet', [$invoice, $amount], true) === false) {
            return false;
        }

        return $walletLog;
    }
}