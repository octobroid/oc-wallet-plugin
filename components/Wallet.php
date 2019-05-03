<?php namespace Octobro\Wallet\Components;

use Auth;
use Flash;
use ApplicationException;
use Cms\Classes\ComponentBase;
use Octobro\Wallet\Classes\Wallet as WalletHelper;
use Responsiv\Pay\Models\Invoice;
use Responsiv\Pay\Models\PaymentMethod as TypeModel;
use Responsiv\Pay\Models\InvoiceItem;
use Octobro\Wallet\Models\Log as WalletLog;

class Wallet extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Wallet Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'invoiceHash' => [
                'title'       => 'Invoice Hash',
                'description' => 'The URL route parameter used for looking up the invoice by its hash.',
                'default'     => '{{ :invoiceHash }}',
                'type'        => 'string'
            ],
            'ownerClass' => [
                'title'         => 'Owner Class',
                'description'   => 'The class name used by owner model.',
                'default'       => '{{ :ownerClass }}',
                'type'          => 'string'
            ],
            'ownerId' => [
                'title'         => 'Owner ID',
                'description'   => 'The ID of owner model.',
                'default'       => '{{ :ownerId }}',
                'type'          => 'string'
            ]
        ];
    }

    public function onRun()
    {
        if (!$this->property('ownerClass')) throw new ApplicationException('Owner class not found');

        if (!class_exists($this->propery('ownerClass'))) throw new ApplicationException('Class for invoice owner not found');

        if (!$this->property('ownerId')) throw new ApplicationException('Owner ID not found');

        $this->page['owner'] = $owner = $this->property('ownerClass')::find($this->property('ownerId'));

        if (!$owner) throw new ApplicationException('Owner not found');
    }

    public function onToggleWallet()
    {
        if (!$this->property('invoiceHash')) throw new ApplicationException('Invoice hash not found');

        if (!$this->property('ownerClass')) throw new ApplicationException('Owner class not found');

        if (!class_exists($this->propery('ownerClass'))) throw new ApplicationException('Class for invoice owner not found');

        if (!$this->property('ownerId')) throw new ApplicationException('Owner ID not found');

        $invoice = Invoice::whereHash($this->property('invoiceHash'))->first();

        if (! $invoice) {
            throw new ApplicationException('Invoice not found');
        }

        $owner = $this->property('ownerClass')::find($this->property('ownerId'));

        if (!$owner) throw new ApplicationException('Owner not found');

        /**
         * User could pay with their whole wallet amount.
         **/
        if ($owner->wallet_amount >= $invoice->total and post('use_wallet') == 0) {
            $this->page['invoice'] = $invoice;

            return true;
        }

        /**
         * Wallet amount could only pay for some of the invoice
         **/
        $this->page['paymentMethods'] = TypeModel::listApplicable($invoice->country_id);
    }

    /**
     * Ajax for paying invoice using wallet
     */
    public function onUseWallet()
    {
        if (!$this->property('invoiceHash')) {
            throw new ApplicationException('Invoice hash not found');
        }

        if (!$this->property('ownerClass')) {
            throw new ApplicationException('Class name for invoice owner not found');
        }

        if (!class_exists($this->propery('ownerClass'))) {
            throw new ApplicationException('Class for invoice owner not found');
        }

        if (!$this->property('ownerId')) {
            throw new ApplicationException('Owner ID not found');
        }

        if (post('use_wallet') <= 0) return;

        if (post('wallet_amount') <= 0) return;

        $invoice = Invoice::whereHash($this->property('invoiceHash'))->first();

        if (!$invoice) {
            throw new ApplicationException('Invoice not found');
        }

        $owner = $this->property('ownerClass')::find($this->property('ownerId'));

        if (!$owner) {
            throw new ApplicationException('Owner data not found');
        }

        $ownerName = Schema::hasColumn($owner->getTable(), 'name') ? $owner->name : post('owner_name');

        $amount = post('use_full_wallet') == true ? $invoice->total : post('wallet_amount');

        WalletHelper::use($owner, $ownerName, $invoice, $amount, null);

        return \Redirect::to($invoice->getReceiptUrl());
    }
}
