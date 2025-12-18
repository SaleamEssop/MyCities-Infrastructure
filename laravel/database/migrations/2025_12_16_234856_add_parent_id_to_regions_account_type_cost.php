<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentIdToRegionsAccountTypeCost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('regions_account_type_cost', function (Blueprint $table) {
            // Parent ID for date child relationship
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('regions_account_type_cost')
                  ->onDelete('set null');
            
            // Add index for faster hierarchy lookups
            $table->index('parent_id');
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
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
}
