<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RenameRegionCostToTariffTemplate extends Migration
{
    /**
     * Run the migrations.
     * This migration:
     * 1. Renames region_costs table to tariff_templates
     * 2. Creates new tables for fixed costs and customer editable costs
     * 3. Clears test data while keeping reference data
     */
    public function up()
    {
        // 1. Rename region_costs table to tariff_templates (if exists)
        if (Schema::hasTable('region_costs') && !Schema::hasTable('tariff_templates')) {
            Schema::rename('region_costs', 'tariff_templates');
        }

        // 2. Add fixed_costs and customer_costs columns to regions_account_type_cost table
        if (Schema::hasTable('regions_account_type_cost')) {
            Schema::table('regions_account_type_cost', function (Blueprint $table) {
                if (!Schema::hasColumn('regions_account_type_cost', 'fixed_costs')) {
                    $table->json('fixed_costs')->nullable()->after('additional');
                }
                if (!Schema::hasColumn('regions_account_type_cost', 'customer_costs')) {
                    $table->json('customer_costs')->nullable()->after('fixed_costs');
                }
            });
        }

        // 3. Create template_fixed_costs table for normalized storage
        if (!Schema::hasTable('template_fixed_costs')) {
            Schema::create('template_fixed_costs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('template_id');
                $table->string('name');
                $table->decimal('value', 12, 2);  // Allows negative for rebates
                $table->timestamps();

                $table->foreign('template_id')
                    ->references('id')
                    ->on('regions_account_type_cost')
                    ->onDelete('cascade');
            });
        }

        // 4. Create template_customer_editable_costs table
        if (!Schema::hasTable('template_customer_editable_costs')) {
            Schema::create('template_customer_editable_costs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('template_id');
                $table->string('name');
                $table->decimal('default_value', 12, 2)->nullable();  // Can be empty
                $table->timestamps();

                $table->foreign('template_id')
                    ->references('id')
                    ->on('regions_account_type_cost')
                    ->onDelete('cascade');
            });
        }

        // 5. Create customer_cost_overrides table for account-specific overrides
        if (!Schema::hasTable('customer_cost_overrides')) {
            Schema::create('customer_cost_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_editable_cost_id');
                $table->unsignedBigInteger('account_id');
                $table->decimal('value', 12, 2);
                $table->timestamps();

                $table->foreign('customer_editable_cost_id')
                    ->references('id')
                    ->on('template_customer_editable_costs')
                    ->onDelete('cascade');

                // Only add foreign key if accounts table exists
                if (Schema::hasTable('accounts')) {
                    $table->foreign('account_id')
                        ->references('id')
                        ->on('accounts')
                        ->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Drop new tables
        Schema::dropIfExists('customer_cost_overrides');
        Schema::dropIfExists('template_customer_editable_costs');
        Schema::dropIfExists('template_fixed_costs');

        // Remove new columns from regions_account_type_cost
        if (Schema::hasTable('regions_account_type_cost')) {
            Schema::table('regions_account_type_cost', function (Blueprint $table) {
                if (Schema::hasColumn('regions_account_type_cost', 'fixed_costs')) {
                    $table->dropColumn('fixed_costs');
                }
                if (Schema::hasColumn('regions_account_type_cost', 'customer_costs')) {
                    $table->dropColumn('customer_costs');
                }
            });
        }

        // Rename tariff_templates back to region_costs (if needed)
        if (Schema::hasTable('tariff_templates') && !Schema::hasTable('region_costs')) {
            Schema::rename('tariff_templates', 'region_costs');
        }
    }
}
