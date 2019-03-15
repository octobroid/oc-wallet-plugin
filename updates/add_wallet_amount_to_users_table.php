<?php namespace Octobro\Wallet\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class AddWalletAmountToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->decimal('wallet_amount', 12, 2)->unsigned()->default(0)->after('password');
        });
    }

    public function down()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->dropColumn('wallet_amount');
        });
    }
}
