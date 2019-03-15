<?php namespace Octobro\Wallet\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class ModifyRelatedColumnsOnLogsTable extends Migration
{
    public function up()
    {
        Schema::table('octobro_wallet_logs', function(Blueprint $table) {
            $table->integer('related_id')->unsigned()->nullable()->change();
            $table->string('related_type')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('octobro_wallet_logs', function(Blueprint $table) {
            $table->integer('related_id')->unsigned()->change();
            $table->string('related_type')->change();
        });
    }
}
