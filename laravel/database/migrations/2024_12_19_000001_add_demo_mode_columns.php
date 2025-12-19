<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDemoModeColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add demo_mode to settings table
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'demo_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('demo_mode')->default(true)->after('landing_subtitle');
            });
        }

        // Add is_demo to ads table
        if (Schema::hasTable('ads') && !Schema::hasColumn('ads', 'is_demo')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('priority');
            });
        }

        // Add is_demo to pages table
        if (Schema::hasTable('pages') && !Schema::hasColumn('pages', 'is_demo')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('is_active');
            });
        }

        // Add is_demo to users table
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_demo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('remember_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('settings', 'demo_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('demo_mode');
            });
        }

        if (Schema::hasColumn('ads', 'is_demo')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }

        if (Schema::hasColumn('pages', 'is_demo')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }

        if (Schema::hasColumn('users', 'is_demo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }
    }
}
