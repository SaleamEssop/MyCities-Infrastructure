<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomCostsToSitesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sites')) {
            Schema::table('sites', function (Blueprint $table) {
                if (!Schema::hasColumn('sites', 'use_custom_costs')) {
                    $table->boolean('use_custom_costs')->default(false);
                }
                if (!Schema::hasColumn('sites', 'custom_electricity_cost')) {
                    $table->decimal('custom_electricity_cost', 10, 2)->nullable();
                }
                if (!Schema::hasColumn('sites', 'custom_water_cost')) {
                    $table->decimal('custom_water_cost', 10, 2)->nullable();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('sites')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropColumn(['use_custom_costs', 'custom_electricity_cost', 'custom_water_cost']);
            });
        }
    }
}
