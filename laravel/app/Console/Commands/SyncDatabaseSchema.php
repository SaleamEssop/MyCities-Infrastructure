<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Settings;

class SyncDatabaseSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sync-schema {--from=internal : Source database (internal|external)} {--to=external : Target database (internal|external)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize database schema from one database to another';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $from = $this->option('from');
        $to = $this->option('to');

        $this->info('========================================');
        $this->info("  Syncing Schema: {$from} → {$to}");
        $this->info('========================================');
        $this->newLine();

        // Setup connections
        $fromConnection = $from === 'internal' ? 'mysql' : 'external_mysql';
        $toConnection = $to === 'internal' ? 'mysql' : 'external_mysql';

        // Configure external connection if needed
        if ($from === 'external' || $to === 'external') {
            if (!$this->configureExternalConnection()) {
                return 1;
            }
        }

        try {
            // Get tables from source
            $this->info("Analyzing {$from} database...");
            $tables = $this->getTables($fromConnection);
            $this->line("  Found " . count($tables) . " tables");

            $this->newLine();
            $this->info("Comparing schemas...");

            $different = 0;
            $checked = 0;

            foreach ($tables as $table) {
                $fromStructure = $this->getTableStructure($fromConnection, $table);
                $toStructure = $this->getTableStructure($toConnection, $table);

                if ($fromStructure !== $toStructure) {
                    $this->warn("  ⚠ Table '{$table}' differs");
                    $different++;
                }
                $checked++;
            }

            $this->newLine();
            
            if ($different === 0) {
                $this->info("✓ All {$checked} tables are identical!");
                return 0;
            }

            $this->warn("Found {$different} table(s) with schema differences.");
            $this->newLine();
            
            if (!$this->confirm('Do you want to copy the schema from ' . $from . ' to ' . $to . '?', false)) {
                $this->info('Sync cancelled.');
                return 0;
            }

            // Perform sync
            $this->info('Syncing schemas...');
            $bar = $this->output->createProgressBar(count($tables));

            foreach ($tables as $table) {
                $createStatement = $this->getCreateStatement($fromConnection, $table);
                
                // Drop and recreate table
                DB::connection($toConnection)->statement("DROP TABLE IF EXISTS `{$table}`");
                DB::connection($toConnection)->statement($createStatement);
                
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info('✓ Schema synchronized successfully!');
            $this->warn('⚠ Note: Data was NOT copied, only table structures.');

        } catch (\Exception $e) {
            $this->error('✗ Sync failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function configureExternalConnection()
    {
        $settings = Settings::first();

        if (!$settings || !$settings->external_db_host) {
            $this->error('External database not configured.');
            return false;
        }

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
            'strict' => true,
        ]);

        // Test connection
        try {
            DB::connection('external_mysql')->getPdo();
            return true;
        } catch (\Exception $e) {
            $this->error('Cannot connect to external database: ' . $e->getMessage());
            return false;
        }
    }

    private function getTables($connection)
    {
        $tables = DB::connection($connection)->select('SHOW TABLES');
        $databaseName = DB::connection($connection)->getDatabaseName();
        $key = "Tables_in_{$databaseName}";
        
        return array_map(function($table) use ($key) {
            return $table->$key;
        }, $tables);
    }

    private function getTableStructure($connection, $table)
    {
        $columns = DB::connection($connection)->select("DESCRIBE `{$table}`");
        return json_encode($columns);
    }

    private function getCreateStatement($connection, $table)
    {
        $result = DB::connection($connection)->select("SHOW CREATE TABLE `{$table}`");
        return $result[0]->{'Create Table'};
    }
}
