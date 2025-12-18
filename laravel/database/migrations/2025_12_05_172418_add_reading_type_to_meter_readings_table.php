<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReadingTypeToMeterReadingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            if (!Schema::hasColumn('meter_readings', 'reading_type')) {
                $table->enum('reading_type', ['ACTUAL', 'FINAL_ACTUAL', 'ESTIMATED', 'FINAL_ESTIMATED'])
                      ->default('ACTUAL')
                      ->after('reading_value');
            }
            if (!Schema::hasColumn('meter_readings', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('reading_type');
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
        Schema::table('meter_readings', function (Blueprint $table) {
            if (Schema::hasColumn('meter_readings', 'reading_type')) {
                $table->dropColumn('reading_type');
            }
            if (Schema::hasColumn('meter_readings', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
}
