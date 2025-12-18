<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegionsAccountTypeCostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regions_account_type_cost', function (Blueprint $table) {
            $table->id();
            $table->string('template_name')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('account_type_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('is_water')->default(0);
            $table->integer('is_electricity')->default(0);
            $table->integer('water_used')->default(0);
            $table->integer('electricity_used')->default(0);
            $table->json('water_in')->nullable();
            $table->json('water_out')->nullable();
            $table->json('electricity')->nullable();
            $table->json('additional')->nullable();
            $table->double('vat_rate')->nullable();
            $table->double('vat_percentage')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('regions_account_type_cost');
    }
}
