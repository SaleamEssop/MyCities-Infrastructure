<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToRegions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->integer('water_base_unit')->nullable()->after('name');
            $table->integer('water_base_unit_cost')->nullable()->after('name');
            $table->integer('electricity_base_unit')->nullable()->after('name');
            $table->integer('electricity_base_unit_cost')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('water_base_unit');
            $table->dropColumn('water_base_unit_cost');
            $table->dropColumn('electricity_base_unit');
            $table->dropColumn('electricity_base_unit_cost');
        });
    }
}
