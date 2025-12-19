<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'landing_background')) {
                $table->string('landing_background', 500)->nullable()->after('terms_condition');
            }
            if (!Schema::hasColumn('settings', 'landing_title')) {
                $table->string('landing_title', 255)->nullable()->after('landing_background');
            }
            if (!Schema::hasColumn('settings', 'landing_subtitle')) {
                $table->text('landing_subtitle')->nullable()->after('landing_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'landing_subtitle')) {
                $table->dropColumn('landing_subtitle');
            }
            if (Schema::hasColumn('settings', 'landing_title')) {
                $table->dropColumn('landing_title');
            }
            if (Schema::hasColumn('settings', 'landing_background')) {
                $table->dropColumn('landing_background');
            }
        });
    }
};
