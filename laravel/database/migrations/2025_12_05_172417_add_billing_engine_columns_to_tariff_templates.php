<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBillingEngineColumnsToTariffTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('regions_account_type_cost', function (Blueprint $table) {
            if (!Schema::hasColumn('regions_account_type_cost', 'billing_type')) {
                $table->enum('billing_type', ['MONTHLY', 'DATE_TO_DATE'])->default('MONTHLY')->after('template_name');
            }
            if (!Schema::hasColumn('regions_account_type_cost', 'effective_from')) {
                $table->date('effective_from')->nullable()->after('billing_type');
            }
            if (!Schema::hasColumn('regions_account_type_cost', 'effective_to')) {
                $table->date('effective_to')->nullable()->after('effective_from');
            }
            if (!Schema::hasColumn('regions_account_type_cost', 'replaced_by_id')) {
                $table->unsignedBigInteger('replaced_by_id')->nullable()->after('effective_to');
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
        Schema::table('regions_account_type_cost', function (Blueprint $table) {
            if (Schema::hasColumn('regions_account_type_cost', 'billing_type')) {
                $table->dropColumn('billing_type');
            }
            if (Schema::hasColumn('regions_account_type_cost', 'effective_from')) {
                $table->dropColumn('effective_from');
            }
            if (Schema::hasColumn('regions_account_type_cost', 'effective_to')) {
                $table->dropColumn('effective_to');
            }
            if (Schema::hasColumn('regions_account_type_cost', 'replaced_by_id')) {
                $table->dropColumn('replaced_by_id');
            }
        });
    }
}
