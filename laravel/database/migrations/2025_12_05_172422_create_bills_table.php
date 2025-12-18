<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('bills')) {
            Schema::create('bills', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('billing_cycle_id')->nullable();
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('meter_id');
                $table->unsignedBigInteger('tariff_template_id');
                $table->unsignedBigInteger('opening_reading_id')->nullable();
                $table->unsignedBigInteger('closing_reading_id')->nullable();
                $table->decimal('consumption', 12, 4);
                $table->decimal('tiered_charge', 10, 2);
                $table->decimal('fixed_costs_total', 10, 2)->default(0);
                $table->decimal('vat_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2);
                $table->boolean('is_provisional')->default(false);
                $table->timestamps();

                $table->foreign('billing_cycle_id')
                      ->references('id')
                      ->on('billing_cycles')
                      ->onDelete('set null');

                $table->foreign('account_id')
                      ->references('id')
                      ->on('accounts')
                      ->onDelete('cascade');

                $table->foreign('meter_id')
                      ->references('id')
                      ->on('meters')
                      ->onDelete('cascade');

                $table->foreign('tariff_template_id')
                      ->references('id')
                      ->on('regions_account_type_cost')
                      ->onDelete('cascade');

                $table->foreign('opening_reading_id')
                      ->references('id')
                      ->on('meter_readings')
                      ->onDelete('set null');

                $table->foreign('closing_reading_id')
                      ->references('id')
                      ->on('meter_readings')
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
        Schema::dropIfExists('bills');
    }
}
