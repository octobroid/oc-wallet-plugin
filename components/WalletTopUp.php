<?php namespace Octobro\Wallet\Components;

use Auth;
use ApplicationException;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Octobro\Wallet\Models\TopUp;

class WalletTopUp extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'walletTopUp Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
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
            'payPage' => [
                'title'       => 'Payment page',
                'description' => 'Name of the payment page file for the "Pay this invoice" links.',
                'type'        => 'dropdown'
            ],
            'minAmount' => [
                'title'       => 'Minimum Amount',
                'description' => 'Minimum amount of topup',
                'type'        => 'integer',
                'default'     => 1,
            ],
        ];
    }

    public function getPropertyOptions($property)
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
    }
    
    public function onSubmit()
    {
        $amount = post('amount');

        if ($amount < $this->property('minAmount')) {
            throw new ApplicationException(sprintf('Minimum top up is %s', $this->property('minAmount')));
        }

        $owner = $this->property('ownerClass')::find($this->property('ownerId'));

        if (!$owner) {
            throw new ApplicationException('Owner not found.');
        }

        $topUp = new TopUp();
        $topUp->owner = $owner;
        $topUp->amount = $amount;
        $topUp->save();

        $invoice = $topUp->createInvoice(Auth::getUser());

        $invoice->setUrlPageName($this->property('payPage'));

        return redirect($invoice->url);
    }
}
