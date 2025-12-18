<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixMeterReadingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = 'meter_readings';
//        // Step 1: Create the new column with the correct datetime type
//        Schema::table($table, function (Blueprint $table) {
//            $table->date('reading_date_new')->nullable()->index();
//        });
//
//        // Step 2: Fetch each record and update the new column with correct datetime values
//        \App\Models\MeterReadings::query()->chunk(100, function ($models) {
//            foreach ($models as $model) {
//                $model->reading_date_new = \Carbon\Carbon::create($model->reading_date)->format('Y-m-d');
//                $model->save();
//            }
//        });
//
//        // Step 3: Drop the old column
//        Schema::table($table, function (Blueprint $table) {
//            $table->dropColumn('reading_date');
//        });

        // Step 4: Rename the new column to the old column name
//        Schema::table($table, function (Blueprint $table) {
//            $table->renameColumn('reading_date_new', 'reading_date');
//        });
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
