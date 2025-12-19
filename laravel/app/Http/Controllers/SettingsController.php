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
        
        // Get deployment tracking information
        $deploymentInfo = $this->getDeploymentInfo();
        
        return view('admin.settings', compact('settings', 'deploymentInfo'));
    }

    /**
     * Get deployment tracking information from server
     */
    private function getDeploymentInfo()
    {
        $info = [
            'deployment_number' => null,
            'last_deployment_id' => null,
            'last_deployment_time' => null,
            'deployment_status' => null,
            'current_commit' => null,
            'repos_sync_status' => [],
        ];

        try {
            // Try to read deployment tracking files from server
            // These files are created by deploy.bat on the server
            $basePath = base_path();
            
            // Check if we're running in Docker (server) or local
            $deploymentNumberFile = $basePath . '/../.last_deployment_number';
            $deploymentIdFile = $basePath . '/../.last_deployment_id';
            $deploymentTimeFile = $basePath . '/../.last_deployment_time';
            $deploymentStatusFile = $basePath . '/../.deployment_status';
            
            if (file_exists($deploymentNumberFile)) {
                $info['deployment_number'] = trim(file_get_contents($deploymentNumberFile));
            }
            
            if (file_exists($deploymentIdFile)) {
                $info['last_deployment_id'] = trim(file_get_contents($deploymentIdFile));
            }
            
            if (file_exists($deploymentTimeFile)) {
                $info['last_deployment_time'] = trim(file_get_contents($deploymentTimeFile));
            }
            
            if (file_exists($deploymentStatusFile)) {
                $info['deployment_status'] = trim(file_get_contents($deploymentStatusFile));
            }
            
            // Get current git commit hash if available
            $gitPath = $basePath . '/.git';
            if (is_dir($gitPath)) {
                $commitHash = trim(shell_exec('cd ' . escapeshellarg($basePath) . ' && git rev-parse HEAD 2>/dev/null'));
                if ($commitHash) {
                    $info['current_commit'] = $commitHash;
                }
            }
            
            // Check repository sync status (compare GitHub vs Server)
            $info['repos_sync_status'] = $this->checkRepoSyncStatus();
            
        } catch (\Exception $e) {
            \Log::warning('Could not read deployment info: ' . $e->getMessage());
        }

        return $info;
    }

    /**
     * Check if server repositories are in sync with GitHub
     */
    private function checkRepoSyncStatus()
    {
        $syncStatus = [];
        
        // Define repositories to check
        $repos = [
            'infrastructure' => [
                'name' => 'Infrastructure',
                'path' => base_path() . '/..',
                'remote' => 'origin/main',
            ],
            'laravel' => [
                'name' => 'Laravel',
                'path' => base_path(),
                'remote' => 'origin/main',
            ],
            'vue' => [
                'name' => 'Vue-Quasar',
                'path' => base_path() . '/../vue-quasar',
                'remote' => 'origin/main',
            ],
        ];
        
        foreach ($repos as $key => $repo) {
            $status = [
                'name' => $repo['name'],
                'in_sync' => false,
                'server_commit' => null,
                'github_commit' => null,
                'behind' => false,
                'error' => null,
            ];
            
            try {
                $repoPath = $repo['path'];
                
                // Check if git directory exists
                if (!is_dir($repoPath . '/.git')) {
                    $status['error'] = 'Not a git repository';
                    $syncStatus[$key] = $status;
                    continue;
                }
                
                // Get server's current commit
                $serverCommit = trim(shell_exec(
                    'cd ' . escapeshellarg($repoPath) . 
                    ' && git rev-parse HEAD 2>/dev/null'
                ));
                
                if (!$serverCommit) {
                    $status['error'] = 'Could not get server commit';
                    $syncStatus[$key] = $status;
                    continue;
                }
                
                $status['server_commit'] = $serverCommit;
                
                // Fetch latest from GitHub (without merging)
                shell_exec(
                    'cd ' . escapeshellarg($repoPath) . 
                    ' && git fetch origin 2>/dev/null'
                );
                
                // Get GitHub's latest commit
                $githubCommit = trim(shell_exec(
                    'cd ' . escapeshellarg($repoPath) . 
                    ' && git rev-parse ' . escapeshellarg($repo['remote']) . ' 2>/dev/null'
                ));
                
                if (!$githubCommit) {
                    $status['error'] = 'Could not get GitHub commit';
                    $syncStatus[$key] = $status;
                    continue;
                }
                
                $status['github_commit'] = $githubCommit;
                
                // Check if server is behind GitHub
                $commitsBehind = trim(shell_exec(
                    'cd ' . escapeshellarg($repoPath) . 
                    ' && git rev-list --count HEAD..' . escapeshellarg($repo['remote']) . ' 2>/dev/null'
                ));
                
                if ($commitsBehind === '') {
                    $commitsBehind = 0;
                } else {
                    $commitsBehind = (int)$commitsBehind;
                }
                
                // Check if in sync
                if ($serverCommit === $githubCommit) {
                    $status['in_sync'] = true;
                } else {
                    $status['behind'] = $commitsBehind > 0;
                }
                
            } catch (\Exception $e) {
                $status['error'] = 'Error checking sync: ' . $e->getMessage();
            }
            
            $syncStatus[$key] = $status;
        }
        
        return $syncStatus;
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
