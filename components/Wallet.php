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
        return [];
    }

    public function onToggleWallet()
    {
        $user = Auth::getUser();
        $invoice = Invoice::whereHash(post('invoice_hash'))->first();

        if (! $invoice) {
            throw new ApplicationException('Invoice not found');
        }

        /**
         * User dapat membayar penuh dengan saldo dompet
         **/
        if ($user->wallet_amount >= $invoice->total and post('use_wallet') == 0) {
            $this->page['invoice'] = $invoice;

            return true;
        }

        /**
         * Saldo dompet hanya dapat membayar sebagian dari total tagihan
         **/
        $this->page['paymentMethods'] = TypeModel::listApplicable($invoice->country_id);
    }

    /**
     * Ajax handler untuk membayar sebagian dengan dompet 
     */
    public function onUseWallet()
    {
        if (post('use_wallet') <= 0) return;

        $invoice = Invoice::whereHash(post('invoice_hash'))->first();

        if (! $invoice) {
            throw new ApplicationException('Invoice not found');
        }

        WalletHelper::use($invoice);

        return json_encode([
            'invoice_total' => $invoice->total
        ]);
    }

    /**
     * Ajax handler untuk membayar penuh dengan dompet 
     */
    public function onFullPayment()
    {
        $user = Auth::getUser();

        $invoice = Invoice::whereHash(post('invoice_hash'))->first();

        if (! $invoice) {
            throw new ApplicationException('Invoice not found');
        }

        // Use all the wallet
        WalletHelper::use($invoice);

        return redirect()->to(post('redirect'));
    }
}
