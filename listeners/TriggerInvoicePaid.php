<?php namespace Octobro\Wallet\Listeners;

use Octobro\Wallet\Classes\Wallet;

class TriggerInvoicePaid
{
    public function handle($invoice)
    {
        $invoice->items->each(function ($item) use ($invoice) {
            if ($item->related instanceof \Octobro\Wallet\Models\TopUp) {
                $topUp = $item->related;
                $topUp->markAsPaid();
            }

            if ($item->related instanceof \Octobro\Wallet\Models\Usage) {
                Wallet::use($invoice);
            }
        });
    }
}
