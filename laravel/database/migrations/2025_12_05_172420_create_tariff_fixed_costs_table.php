<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTariffFixedCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('tariff_fixed_costs')) {
            Schema::create('tariff_fixed_costs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tariff_template_id');
                $table->string('name');
                $table->decimal('amount', 10, 2);
                $table->boolean('is_vatable')->default(true);
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
        Schema::dropIfExists('tariff_fixed_costs');
    }
}
