<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldRegionAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('regions_account_type_cost', function (Blueprint $table) {
            $table->date('billing_date')->nullable()->after('ratable_value');
            $table->date('reading_date')->nullable()->after('billing_date');
            $table->json('waterin_additional')->nullable()->after('water_in');
            $table->json('waterout_additional')->nullable()->after('water_out');
            $table->json('electricity_additional')->nullable()->after('electricity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
