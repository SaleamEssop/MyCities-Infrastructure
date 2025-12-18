<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            if (!Schema::hasColumn('meter_readings', 'reading_type')) {
                $table->enum('reading_type', ['ACTUAL', 'ESTIMATED', 'CALCULATED'])
                    ->default('ACTUAL')
                    ->after('reading_value');
            }
            if (!Schema::hasColumn('meter_readings', 'notes')) {
                $table->text('notes')->nullable()->after('reading_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            if (Schema::hasColumn('meter_readings', 'reading_type')) {
                $table->dropColumn('reading_type');
            }
            if (Schema::hasColumn('meter_readings', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};