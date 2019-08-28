<?php namespace Octobro\Wallet\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class AddIsUseWalletToInvoiceTable extends Migration
{
    public function up()
    {
        Schema::table('responsiv_pay_invoices', function(Blueprint $table) {
            $table->boolean('is_use_wallet')->default(false)->after('payment_method_id');
        });
    }

    public function down()
    {
        Schema::table('responsiv_pay_invoices', function(Blueprint $table) {
            $table->dropColumn('is_use_wallet');
        });
    }
}
