<?php namespace Octobro\Wallet\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateLogsTable extends Migration
{
    public function up()
    {
        Schema::create('octobro_wallet_logs', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();
            $table->text('description')->nullable();
            $table->decimal('previous_amount', 12, 2)->unsigned()->default(0);
            $table->decimal('updated_amount', 12, 2)->unsigned();
            $table->decimal('amount', 12, 2);
            $table->morphs('related');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('octobro_wallet_logs');
    }
}
