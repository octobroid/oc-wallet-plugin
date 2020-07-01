<?php

/**
 * Resonsiv.pay
 **/
Event::listen('responsiv.pay.invoicePaid', 'Octobro\Wallet\Listeners\TriggerInvoicePaid');

Event::listen('responsiv.pay.beforeUpdateInvoiceStatus', 'Octobro\Wallet\Listeners\TriggerInvoiceStatusUpdated');