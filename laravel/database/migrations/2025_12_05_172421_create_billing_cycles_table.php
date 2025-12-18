<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingCyclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('billing_cycles')) {
            Schema::create('billing_cycles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->date('cycle_start');
                $table->date('cycle_end');
                $table->enum('status', ['provisional', 'final'])->default('provisional');
                $table->timestamps();

                $table->foreign('account_id')
                      ->references('id')
                      ->on('accounts')
                      ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_cycles');
    }
}
