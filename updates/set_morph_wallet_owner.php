<?php namespace Octobro\Wallet\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class SetMorphWalletOwner extends Migration
{
    public function up()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->dropColumn('wallet_amount');
        });

        Schema::table('octobro_wallet_logs', function(Blueprint $table) {
            $table->dropColumn('user_id');

            $table->string('owner_name')->nullable()->after('id');
            $table->string('owner_id')->nullable()->index()->after('owner_name');
            $table->string('owner_type')->nullable()->index()->after('owner_id');
        });
    }

    public function down()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->decimal('wallet_amount', 12, 2)->unsigned()->default(0)->after('password');
        });

        Schema::table('octobro_wallet_logs', function(Blueprint $table) {
            $table->integer('user_id')->unsigned()->index()->after('id');

            $table->dropColumn('owner_name');
            $table->dropColumn('owner_id');
            $table->dropColumn('owner_type');
        });
    }
}
