<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('adjustments')) {
            Schema::create('adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bill_id');
                $table->decimal('original_charge', 10, 2);
                $table->decimal('final_charge', 10, 2);
                $table->decimal('adjustment_amount', 10, 2);
                $table->unsignedBigInteger('applied_to_bill_id')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->foreign('bill_id')
                      ->references('id')
                      ->on('bills')
                      ->onDelete('cascade');

                $table->foreign('applied_to_bill_id')
                      ->references('id')
                      ->on('bills')
                      ->onDelete('set null');
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
        Schema::dropIfExists('adjustments');
    }
}
