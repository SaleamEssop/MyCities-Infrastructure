<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use App\Models\Settings;

class MigrateBothDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:migrate-both {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations on both internal and external databases to keep schemas in sync';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('  Migrating BOTH Databases');
        $this->info('========================================');
        $this->newLine();

        // Step 1: Migrate Internal Database
        $this->info('[1/2] Migrating INTERNAL database (container MySQL)...');
        try {
            DB::setDefaultConnection('mysql');
            Artisan::call('migrate', [
                '--database' => 'mysql',
                '--force' => $this->option('force')
            ]);
            $this->info('✓ Internal database migrated successfully');
            $this->line(Artisan::output());
        } catch (\Exception $e) {
            $this->error('✗ Internal database migration failed: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Step 2: Migrate External Database (if configured)
        $this->info('[2/2] Migrating EXTERNAL database...');
        
        $settings = Settings::first();
        
        if (!$settings || !$settings->external_db_host) {
            $this->warn('⚠ External database not configured. Skipping.');
            $this->line('  Configure external DB in: Admin > Settings');
            return 0;
        }

        try {
            // Configure external connection dynamically
            Config::set('database.connections.external_mysql', [
                'driver' => 'mysql',
                'host' => $settings->external_db_host,
                'port' => $settings->external_db_port ?? 3306,
                'database' => $settings->external_db_database,
                'username' => $settings->external_db_username,
                'password' => decrypt($settings->external_db_password),
                'unix_socket' => env('DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    \PDO::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ]);

            // Test connection
            DB::connection('external_mysql')->getPdo();
            $this->line('  Connected to: ' . $settings->external_db_host);

            // Run migrations
            Artisan::call('migrate', [
                '--database' => 'external_mysql',
                '--force' => $this->option('force')
            ]);
            
            $this->info('✓ External database migrated successfully');
            $this->line(Artisan::output());

        } catch (\Exception $e) {
            $this->error('✗ External database migration failed: ' . $e->getMessage());
            $this->newLine();
            $this->warn('External DB Config:');
            $this->line('  Host: ' . ($settings->external_db_host ?? 'N/A'));
            $this->line('  Port: ' . ($settings->external_db_port ?? 'N/A'));
            $this->line('  Database: ' . ($settings->external_db_database ?? 'N/A'));
            $this->line('  Username: ' . ($settings->external_db_username ?? 'N/A'));
            return 1;
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('  ✓ Both databases migrated successfully');
        $this->info('========================================');

        return 0;
    }
}
