<?php namespace Octobro\Wallet\Components;

use Redirect;
use ApplicationException;
use Cms\Classes\ComponentBase;
use Octobro\Wallet\Classes\Wallet as WalletHelper;
use Responsiv\Pay\Models\Invoice;
use Responsiv\Pay\Models\PaymentMethod as TypeModel;

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
            ],
            'ownerName' => [
                'title'         => 'Owner name',
                'description'   => 'The name of the wallet owner.',
                'default'       => '{{ :ownerName }}',
                'type'          => 'string'
            ],
            'updatePartial' => [
                'title'         => 'Update partial',
                'description'   => "Variable used for updating additional partial when 'pay with wallet' checkbox is toggled.",
                'type'          => 'string'
            ]
        ];
    }

    public function onRun()
    {
        if (!$this->property('ownerClass')) throw new ApplicationException('Owner class not found');

        if (!class_exists($this->property('ownerClass'))) throw new ApplicationException('Class for invoice owner not found');

        if (!$this->property('ownerId')) throw new ApplicationException('Owner ID not found');

        $this->page['owner'] = $owner = $this->property('ownerClass')::find($this->property('ownerId'));

        if (!$owner) throw new ApplicationException('Owner not found');

        $this->page['wallet_available_balance'] = WalletHelper::getAvailableBalance($owner);

        $invoice = Invoice::whereHash($this->property('invoiceHash'))->first();

        if (! $invoice) {
            throw new ApplicationException('Invoice not found');
        }

        $this->page['wallet_usage_invoice_item'] = WalletHelper::getWalletUsageInvoiceItem($invoice);

        $this->page['updatePartial'] = $this->property('updatePartial');
    }

    public function onToggleWallet()
    {
        $invoice = Invoice::whereHash($this->property('invoiceHash'))->first();

        if (! $invoice) {
            throw new ApplicationException('Invoice not found');
        }

        $owner = $this->property('ownerClass')::find($this->property('ownerId'));

        if ($invoice->is_use_wallet == 1) {
            WalletHelper::remove($invoice);
        } else {
            WalletHelper::hold($owner, $invoice, post('amount'));
        }

        if (!$this->property('updatePartial')) {
            return redirect()->refresh();
        }

        $this->page['invoice'] = $invoice;
        
        /**
         * Wallet amount could only pay for some of the invoice
         **/
        $this->page['paymentMethods'] = TypeModel::listApplicable($invoice->country_id);
        $this->page['paymentMethod'] = $invoice->payment_method;
    }
}
