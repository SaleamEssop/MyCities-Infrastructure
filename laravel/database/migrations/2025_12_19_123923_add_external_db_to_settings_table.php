<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExternalDbToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('db_mode', ['internal', 'external'])->default('internal')->after('demo_mode');
            $table->string('external_db_host')->nullable()->after('db_mode');
            $table->integer('external_db_port')->default(3306)->after('external_db_host');
            $table->string('external_db_database')->nullable()->after('external_db_port');
            $table->string('external_db_username')->nullable()->after('external_db_database');
            $table->text('external_db_password')->nullable()->after('external_db_username');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'db_mode',
                'external_db_host',
                'external_db_port',
                'external_db_database',
                'external_db_username',
                'external_db_password'
            ]);
        });
    }
}
