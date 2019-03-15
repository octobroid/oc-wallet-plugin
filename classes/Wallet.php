<?php namespace Octobro\Wallet\Classes;

use Event;
use ApplicationException;
use Responsiv\Pay\Models\InvoiceItem;
use Octobro\Wallet\Models\Log as WalletLog;

class Wallet
{
    static function use($invoice, $amount = null)
    {
        $user = $invoice->user;

        if (!$user) {
            throw new ApplicationException('User not found.');
        }

        if (!$amount) {
            // By default, it will use all the wallet amount
            $amount = min($invoice->total, $user->wallet_amount);
        }

        if (!$amount) return;

        if ($user->wallet_amount < $amount) {
            throw new ApplicationException('Insufficient balance.');
        }

        $walletLog = WalletLog::createLog($invoice, $user, (- $amount), null, 'Wallet usage for Invoice #' . $invoice->id);
        
        $invoiceItem = new InvoiceItem([
            'invoice_id'  => $invoice->id,
            'description' => 'Wallet Usage',
            'quantity'    => 1,
            'price'       => 0,
            'discount'    => $amount,
            'related'     => $walletLog,
        ]);

        $invoice->items()->save($invoiceItem);

        $invoice->save();

        if ($invoice->total == 0 && $invoice->markAsPaymentProcessed()) {
            $invoice->updateInvoiceStatus('paid');
        }

        Event::fire('octobro.wallet.afterUseWallet', [$invoice, $amount]);

        return $walletLog;
    }
}