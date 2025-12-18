<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameOnBillToAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('name_on_bill')->nullable()->after('account_number');
            
            // Add customer_costs and fixed_costs columns if they don't exist
            if (!Schema::hasColumn('accounts', 'customer_costs')) {
                $table->json('customer_costs')->nullable();
            }
            if (!Schema::hasColumn('accounts', 'fixed_costs')) {
                $table->json('fixed_costs')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['name_on_bill', 'fixed_costs']);
        });
    }
}
