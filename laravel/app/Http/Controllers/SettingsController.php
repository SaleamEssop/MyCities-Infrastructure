<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SettingsController extends Controller
{
    /**
     * Display general application settings
     */
    public function index()
    {
        $settings = Settings::first() ?? new Settings();
        return view('admin.settings', compact('settings'));
    }

    /**
     * Update general application settings
     */
    public function update(Request $request)
    {
        $validationRules = [
            'demo_mode' => 'required|boolean',
            'db_mode' => 'required|in:internal,external',
        ];

        // If external mode, validate DB credentials
        if ($request->db_mode === 'external') {
            $validationRules['external_db_host'] = 'required|string';
            $validationRules['external_db_port'] = 'required|integer|min:1|max:65535';
            $validationRules['external_db_database'] = 'required|string';
            $validationRules['external_db_username'] = 'required|string';
            
            // Password required only if not already set
            $settings = Settings::first();
            if (!$settings || !$settings->external_db_password) {
                $validationRules['external_db_password'] = 'required|string|min:6';
            }
        }

        $request->validate($validationRules);

        $settings = Settings::first();
        
        if (!$settings) {
            $settings = new Settings();
        }

        // Update demo mode
        $settings->demo_mode = $request->demo_mode;

        // Update database mode
        $previousDbMode = $settings->db_mode;
        $settings->db_mode = $request->db_mode;

        // Update external DB credentials if provided
        if ($request->db_mode === 'external') {
            $settings->external_db_host = $request->external_db_host;
            $settings->external_db_port = $request->external_db_port ?? 3306;
            $settings->external_db_database = $request->external_db_database;
            $settings->external_db_username = $request->external_db_username;
            
            // Encrypt password if provided
            if ($request->filled('external_db_password')) {
                $settings->external_db_password = encrypt($request->external_db_password);
            }

            // Test external connection before saving
            if (!$this->testExternalConnection($settings)) {
                Session::flash('alert-class', 'alert-danger');
                Session::flash('alert-message', 'Cannot connect to external database. Please check your credentials.');
                return redirect()->back()->withInput();
            }
        }

        $settings->save();

        // Clear cache to pick up new settings
        Cache::forget('app_db_mode');
        Cache::forget('external_db_config');

        Session::flash('alert-class', 'alert-success');
        $message = 'Settings updated successfully';
        
        if ($previousDbMode !== $settings->db_mode) {
            $dbName = $settings->db_mode === 'internal' ? 'Internal (Container)' : 'External';
            $message .= ". Database switched to: {$dbName}";
        }
        
        Session::flash('alert-message', $message);

        return redirect()->route('settings.index');
    }

    /**
     * Test external database connection
     */
    private function testExternalConnection($settings)
    {
        try {
            Config::set('database.connections.test_external', [
                'driver' => 'mysql',
                'host' => $settings->external_db_host,
                'port' => $settings->external_db_port ?? 3306,
                'database' => $settings->external_db_database,
                'username' => $settings->external_db_username,
                'password' => is_string($settings->external_db_password) && strpos($settings->external_db_password, 'eyJpdiI6') === 0
                    ? decrypt($settings->external_db_password)
                    : $settings->external_db_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]);

            DB::connection('test_external')->getPdo();
            DB::connection('test_external')->select('SELECT 1');
            
            return true;
        } catch (\Exception $e) {
            \Log::error('External DB connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}
