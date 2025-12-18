<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTariffTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('tariff_tiers')) {
            Schema::create('tariff_tiers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tariff_template_id');
                $table->integer('tier_number');
                $table->decimal('min_units', 12, 4)->default(0);
                $table->decimal('max_units', 12, 4)->nullable(); // NULL means unlimited
                $table->decimal('rate_per_unit', 10, 4);
                $table->timestamps();

                $table->foreign('tariff_template_id')
                      ->references('id')
                      ->on('regions_account_type_cost')
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
        Schema::dropIfExists('tariff_tiers');
    }
}
