<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SimplifyBillingArchitecture extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration simplifies the billing architecture by:
     * 1. Adding tariff_template_id to accounts table
     * 2. Removing account_type_id from accounts table
     * 3. Removing region_id from accounts table (gets region via TariffTemplate)
     * 4. Removing account_type_id from regions_account_type_cost table
     * 5. Dropping the account_type table
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Add tariff_template_id to accounts table with foreign key constraint
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('tariff_template_id')->nullable()->after('site_id');
            $table->foreign('tariff_template_id')
                  ->references('id')
                  ->on('regions_account_type_cost')
                  ->onDelete('set null');
        });

        // Step 2: Remove account_type_id and region_id from accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['account_type_id', 'region_id']);
        });

        // Step 3: Remove account_type_id from regions_account_type_cost table
        Schema::table('regions_account_type_cost', function (Blueprint $table) {
            $table->dropColumn('account_type_id');
        });

        // Step 4: Drop the account_type table
        Schema::dropIfExists('account_type');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recreate the account_type table
        Schema::create('account_type', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
        });

        // Re-add account_type_id to regions_account_type_cost
        Schema::table('regions_account_type_cost', function (Blueprint $table) {
            $table->unsignedBigInteger('account_type_id')->nullable()->after('region_id');
        });

        // Re-add account_type_id and region_id to accounts
        Schema::table('accounts', function (Blueprint $table) {
            $table->integer('region_id')->nullable()->after('site_id');
            $table->integer('account_type_id')->nullable()->after('region_id');
        });

        // Remove tariff_template_id with its foreign key
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['tariff_template_id']);
            $table->dropColumn('tariff_template_id');
        });
    }
}
