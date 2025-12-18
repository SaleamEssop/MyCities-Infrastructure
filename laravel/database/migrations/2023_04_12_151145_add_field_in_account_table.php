<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldInAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->integer('region_id')->nullable()->after('site_id');
            $table->integer('account_type_id')->nullable()->after('region_id');
            $table->string('water_email')->nullable()->after('account_type_id');
            $table->string('electricity_email')->nullable()->after('water_email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('site', function (Blueprint $table) {
            //
        });
    }
}
