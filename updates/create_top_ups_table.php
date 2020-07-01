<?php namespace Octobro\Wallet\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateTopUpsTable extends Migration
{
    public function up()
    {
        Schema::create('octobro_wallet_top_ups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('owner_id')->nullable()->index();
            $table->string('owner_type')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_paid')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('octobro_wallet_top_ups');
    }
}
