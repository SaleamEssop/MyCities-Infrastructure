<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToAccountFixedCosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_fixed_costs', function (Blueprint $table) {
            $table->integer('is_active')->default(1)->after('value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_fixed_costs', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
