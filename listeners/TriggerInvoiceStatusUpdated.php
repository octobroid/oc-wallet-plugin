<?php namespace Octobro\Wallet\Listeners;

use Octobro\Wallet\Classes\Wallet;

class TriggerInvoiceStatusUpdated
{
    public function handle($record, $invoice, $statusId, $previousStatus)
    {
        // If void
        if ($statusId == 4) {
            Wallet::remove($invoice);
        }
    }
}
