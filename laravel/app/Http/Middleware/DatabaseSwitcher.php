<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class DatabaseSwitcher
{
    /**
     * Handle an incoming request and dynamically switch database connection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\ResponseRedirect)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\ResponseRedirect
     */
    public function handle(Request $request, Closure $next)
    {
        // Cache the DB mode check for 60 seconds to reduce DB queries
        $dbMode = Cache::remember('app_db_mode', 60, function () {
            try {
                $settings = DB::table('settings')->first();
                return $settings->db_mode ?? 'internal';
            } catch (\Exception $e) {
                // If settings table doesn't exist yet (during migration), use internal
                return 'internal';
            }
        });

        // If external mode, configure and switch to external database
        if ($dbMode === 'external') {
            $externalConfig = Cache::remember('external_db_config', 60, function () {
                try {
                    $settings = DB::table('settings')->first();
                    
                    if (!$settings || !$settings->external_db_host) {
                        return null;
                    }

                    return [
                        'host' => $settings->external_db_host,
                        'port' => $settings->external_db_port ?? 3306,
                        'database' => $settings->external_db_database,
                        'username' => $settings->external_db_username,
                        'password' => decrypt($settings->external_db_password),
                    ];
                } catch (\Exception $e) {
                    return null;
                }
            });

            if ($externalConfig) {
                // Configure external MySQL connection
                Config::set('database.connections.external_mysql', [
                    'driver' => 'mysql',
                    'host' => $externalConfig['host'],
                    'port' => $externalConfig['port'],
                    'database' => $externalConfig['database'],
                    'username' => $externalConfig['username'],
                    'password' => $externalConfig['password'],
                    'unix_socket' => env('DB_SOCKET', ''),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => null,
                ]);

                try {
                    // Switch default connection to external
                    DB::setDefaultConnection('external_mysql');
                    
                    // Test connection
                    DB::connection('external_mysql')->getPdo();
                    
                } catch (\Exception $e) {
                    // If external connection fails, fall back to internal
                    \Log::error('External DB connection failed, falling back to internal: ' . $e->getMessage());
                    DB::setDefaultConnection('mysql');
                }
            } else {
                // External mode but no config, use internal
                DB::setDefaultConnection('mysql');
            }
        } else {
            // Internal mode - use container MySQL
            DB::setDefaultConnection('mysql');
        }

        return $next($request);
    }
}
