<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Meter;
use App\Models\MeterReadings;
use App\Models\MeterType;
use App\Models\Regions;
use App\Models\RegionsAccountTypeCost;
use App\Models\Settings;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class UserManagementController extends Controller
{
    // Meter type title constants - these match database values
    private const METER_TYPE_WATER = 'water';
    private const METER_TYPE_ELECTRICITY = 'electricity';
    
    // Default meter category ID
    private const DEFAULT_METER_CATEGORY_ID = 1;

    /**
     * Display the user management page
     */
    public function index()
    {
        $settings = Settings::first();
        $demoMode = $settings->demo_mode ?? true;

        if ($demoMode) {
            // Demo mode: show all users including demo accounts
            $users = User::withCount('sites')->get();
        } else {
            // Production mode: hide demo users
            $users = User::where('is_demo', false)->withCount('sites')->get();
        }
        
        $regions = Regions::all();
        $meterTypes = MeterType::all();
        
        return view('admin.user_management.index', [
            'users' => $users,
            'regions' => $regions,
            'meterTypes' => $meterTypes,
        ]);
    }

    /**
     * Get user data with all related entities for editing
     */
    public function getUserData($id)
    {
        $user = User::with([
            'sites.accounts.meters.readings' => function($query) {
                $query->orderBy('reading_date', 'desc')->limit(5);
            },
            'sites.region'
        ])->find($id);
        
        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }
        
        return response()->json(['status' => 200, 'data' => $user]);
    }

    /**
     * Store a new user with all related data
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contact_number' => 'required|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        DB::beginTransaction();
        
        try {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'password' => Hash::make($request->password),
                'is_admin' => 0,
            ]);
            
            // Process sites if provided
            if ($request->has('sites') && is_array($request->sites)) {
                $this->processSites($user->id, $request->sites);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 200, 
                'message' => 'User created successfully',
                'user_id' => $user->id
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500, 
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing user with all related data
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'contact_number' => 'required|string|max:20',
        ]);

        DB::beginTransaction();
        
        try {
            $user = User::findOrFail($id);
            
            // Update user basic info
            $user->name = $request->name;
            $user->email = $request->email;
            $user->contact_number = $request->contact_number;
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            
            $user->save();
            
            // Process sites if provided
            if ($request->has('sites') && is_array($request->sites)) {
                $this->processSites($user->id, $request->sites, true);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 200, 
                'message' => 'User updated successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500, 
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a user and all related data completely
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $user = User::with(['sites.accounts.meters.readings', 'sites.accounts.payments'])->findOrFail($id);
            $userName = $user->name;
            
            // Count what will be deleted
            $deletionSummary = [
                'sites' => $user->sites->count(),
                'accounts' => 0,
                'meters' => 0,
                'readings' => 0,
                'payments' => 0,
            ];
            
            foreach ($user->sites as $site) {
                $deletionSummary['accounts'] += $site->accounts->count();
                foreach ($site->accounts as $account) {
                    $deletionSummary['meters'] += $account->meters->count();
                    $deletionSummary['payments'] += $account->payments->count();
                    foreach ($account->meters as $meter) {
                        $deletionSummary['readings'] += $meter->readings->count();
                    }
                }
            }
            
            // Delete user (cascade will handle the rest via model events)
            $user->delete();
            
            // Also delete any API tokens
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $id)
                ->delete();
            
            DB::commit();
            
            return response()->json([
                'status' => 200, 
                'message' => "User '{$userName}' deleted completely. Removed: {$deletionSummary['sites']} site(s), {$deletionSummary['accounts']} account(s), {$deletionSummary['meters']} meter(s), {$deletionSummary['readings']} reading(s), {$deletionSummary['payments']} payment(s)."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500, 
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a complete test user with sequential naming
     * Username: testuser1, testuser2, testuser3...
     * Email: testuser1@test.com, testuser2@test.com...
     * Password: 123456 (fixed for all test users)
     * Phone: 084 + 7 random digits (e.g., 0841234567)
     */
    public function generateTestUser()
    {
        DB::beginTransaction();
        
        try {
            // Find the next test user number by checking existing test users
            $lastTestUser = User::where('email', 'like', 'testuser%@test.com')
                ->orderByRaw("CAST(REPLACE(REPLACE(email, 'testuser', ''), '@test.com', '') AS UNSIGNED) DESC")
                ->first();
            
            $nextNumber = 1;
            if ($lastTestUser) {
                // Extract number from email like testuser5@test.com -> 5
                preg_match('/testuser(\d+)@test\.com/', $lastTestUser->email, $matches);
                if (!empty($matches[1])) {
                    $nextNumber = (int)$matches[1] + 1;
                }
            }
            
            $username = 'testuser' . $nextNumber;
            $email = $username . '@test.com';
            
            // Generate unique phone number: 084 + 7 random digits
            // Retry up to 10 times to ensure uniqueness
            $phone = null;
            $maxAttempts = 10;
            for ($i = 0; $i < $maxAttempts; $i++) {
                $candidate = '084' . str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
                if (!User::where('contact_number', $candidate)->exists()) {
                    $phone = $candidate;
                    break;
                }
            }
            
            if (!$phone) {
                throw new \Exception('Could not generate unique phone number after ' . $maxAttempts . ' attempts.');
            }
            
            // Create test user with fixed password 123456
            $user = User::create([
                'name' => $username,
                'email' => $email,
                'contact_number' => $phone,
                'password' => Hash::make('123456'),
                'is_admin' => 0,
            ]);
            
            // Get first available region
            $region = Regions::first();
            $waterMeterType = MeterType::where('title', self::METER_TYPE_WATER)->first();
            $electricityMeterType = MeterType::where('title', self::METER_TYPE_ELECTRICITY)->first();
            
            if (!$region) {
                throw new \Exception('No regions found. Please create a region first.');
            }
            
            // Get first available tariff template for this region
            $tariffTemplate = RegionsAccountTypeCost::where('region_id', $region->id)
                ->where('is_active', 1)
                ->first();
            
            // Create test site
            $site = Site::create([
                'user_id' => $user->id,
                'title' => $username . ' Site',
                'lat' => -33.9249,
                'lng' => 18.4241,
                'address' => $nextNumber . ' Test Street, Cape Town, 8001',
                'email' => $email,
                'region_id' => $region->id,
                'billing_type' => 'monthly',
                'site_username' => $username . '_site',
            ]);
            
            // Generate random account name and number
            // Use letter A-Z based on user number (wraps after 26)
            $accountLetter = chr(ord('A') + (($nextNumber - 1) % 26));
            $accountName = 'Account ' . $accountLetter . random_int(100, 999);
            $accountNumber = 'ACC-' . strtoupper(substr(md5($username), 0, 8));
            
            // Create test account
            $account = Account::create([
                'site_id' => $site->id,
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'billing_date' => 15,
                'tariff_template_id' => $tariffTemplate ? $tariffTemplate->id : null,
                'bill_day' => 15,
                'read_day' => 1,
            ]);
            
            $metersCreated = [];
            
            // Randomly decide to create 1 or 2 meters
            $createBothMeters = random_int(0, 1) === 1;
            $createWaterFirst = random_int(0, 1) === 1;
            
            // Create water meter with initial reading
            if ($waterMeterType && ($createBothMeters || $createWaterFirst)) {
                $waterMeter = Meter::create([
                    'account_id' => $account->id,
                    'meter_type_id' => $waterMeterType->id,
                    'meter_category_id' => self::DEFAULT_METER_CATEGORY_ID,
                    'meter_title' => 'Water Meter',
                    'meter_number' => 'WM-' . strtoupper(substr(md5($username . 'water'), 0, 6)),
                ]);
                
                // Add initial reading with random value
                $initialWaterReading = random_int(100, 5000);
                MeterReadings::create([
                    'meter_id' => $waterMeter->id,
                    'reading_date' => now()->format('Y-m-d'),
                    'reading_value' => $initialWaterReading,
                ]);
                
                $metersCreated[] = ['type' => 'Water', 'number' => $waterMeter->meter_number, 'initial_reading' => $initialWaterReading];
            }
            
            // Create electricity meter with initial reading
            if ($electricityMeterType && ($createBothMeters || !$createWaterFirst)) {
                $electricityMeter = Meter::create([
                    'account_id' => $account->id,
                    'meter_type_id' => $electricityMeterType->id,
                    'meter_category_id' => self::DEFAULT_METER_CATEGORY_ID,
                    'meter_title' => 'Electricity Meter',
                    'meter_number' => 'EM-' . strtoupper(substr(md5($username . 'elec'), 0, 6)),
                ]);
                
                // Add initial reading with random value
                $initialElecReading = random_int(1000, 10000);
                MeterReadings::create([
                    'meter_id' => $electricityMeter->id,
                    'reading_date' => now()->format('Y-m-d'),
                    'reading_value' => $initialElecReading,
                ]);
                
                $metersCreated[] = ['type' => 'Electricity', 'number' => $electricityMeter->meter_number, 'initial_reading' => $initialElecReading];
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 200,
                'message' => 'Test user created successfully',
                'user_id' => $user->id,
                'credentials' => [
                    'username' => $username,
                    'email' => $email,
                    'password' => '123456',
                    'phone' => $phone,
                ],
                'account' => [
                    'name' => $accountName,
                    'number' => $accountNumber,
                ],
                'meters' => $metersCreated,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error generating test user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete all test users (emails ending with @test.com)
     */
    public function deleteTestUsers()
    {
        DB::beginTransaction();
        
        try {
            $testUsers = User::where('email', 'like', '%@test.com')->get();
            $count = $testUsers->count();
            
            foreach ($testUsers as $user) {
                $user->delete(); // Cascade delete handled by model
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 200,
                'message' => "Deleted {$count} test user(s) successfully"
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting test users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clone an existing user for testing
     */
    public function cloneUser($id)
    {
        DB::beginTransaction();
        
        try {
            $originalUser = User::with([
                'sites.accounts.meters'
            ])->findOrFail($id);
            
            $timestamp = time();
            
            // Create cloned user
            $newUser = User::create([
                'name' => $originalUser->name . ' (Clone)',
                'email' => 'clone_' . $timestamp . '@test.com',
                'contact_number' => $originalUser->contact_number,
                'password' => Hash::make('password123'),
                'is_admin' => 0,
            ]);
            
            // Clone sites
            foreach ($originalUser->sites as $site) {
                $newSite = Site::create([
                    'user_id' => $newUser->id,
                    'title' => $site->title . ' (Clone)',
                    'lat' => $site->lat,
                    'lng' => $site->lng,
                    'address' => $site->address,
                    'email' => $site->email,
                    'region_id' => $site->region_id,
                    'billing_type' => $site->billing_type,
                    'site_username' => $site->site_username ? $site->site_username . '_clone' : null,
                ]);
                
                // Clone accounts
                foreach ($site->accounts as $account) {
                    $newAccount = Account::create([
                        'site_id' => $newSite->id,
                        'account_name' => $account->account_name,
                        'account_number' => $account->account_number . '-CLONE',
                        'billing_date' => $account->billing_date,
                        'tariff_template_id' => $account->tariff_template_id,
                        'bill_day' => $account->bill_day,
                        'read_day' => $account->read_day,
                    ]);
                    
                    // Clone meters (without readings)
                    foreach ($account->meters as $meter) {
                        Meter::create([
                            'account_id' => $newAccount->id,
                            'meter_type_id' => $meter->meter_type_id,
                            'meter_category_id' => $meter->meter_category_id,
                            'meter_title' => $meter->meter_title,
                            'meter_number' => $meter->meter_number . '-CLONE',
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 200,
                'message' => 'User cloned successfully',
                'user_id' => $newUser->id
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error cloning user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users with filters
     */
    public function search(Request $request)
    {
        $query = User::withCount('sites');
        
        // Search by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Search by address (through sites)
        if ($request->filled('address')) {
            $query->whereHas('sites', function($q) use ($request) {
                $q->where('address', 'like', '%' . $request->address . '%');
            });
        }
        
        // Search by phone
        if ($request->filled('phone')) {
            $query->where('contact_number', 'like', '%' . $request->phone . '%');
        }
        
        // Filter by user type
        if ($request->filled('user_type')) {
            if ($request->user_type === 'test') {
                $query->where('email', 'like', '%@test.com');
            } elseif ($request->user_type === 'real') {
                $query->where('email', 'not like', '%@test.com');
            }
        }
        
        $users = $query->get();
        
        return response()->json([
            'status' => 200,
            'data' => $users
        ]);
    }

    /**
     * Process sites for user create/update
     */
    private function processSites($userId, $sites, $isUpdate = false)
    {
        // Track processed site IDs for update mode
        $processedSiteIds = [];
        
        foreach ($sites as $siteData) {
            $siteId = $siteData['id'] ?? null;
            
            $siteFields = [
                'user_id' => $userId,
                'title' => $siteData['title'] ?? '',
                'lat' => $siteData['lat'] ?? 0,
                'lng' => $siteData['lng'] ?? 0,
                'address' => $siteData['address'] ?? '',
                'email' => $siteData['email'] ?? null,
                'region_id' => $siteData['region_id'] ?? null,
                'billing_type' => $siteData['billing_type'] ?? 'monthly',
                'site_username' => $siteData['site_username'] ?? null,
            ];
            
            if ($siteId && $isUpdate) {
                // Update existing site
                $site = Site::find($siteId);
                if ($site && $site->user_id == $userId) {
                    $site->update($siteFields);
                } else {
                    $site = Site::create($siteFields);
                    $siteId = $site->id;
                }
            } else {
                // Create new site
                $site = Site::create($siteFields);
                $siteId = $site->id;
            }
            
            $processedSiteIds[] = $siteId;
            
            // Process accounts for this site
            if (isset($siteData['accounts']) && is_array($siteData['accounts'])) {
                $this->processAccounts($siteId, $siteData['accounts'], $isUpdate);
            }
        }
        
        // If updating, delete sites not in the processed list
        if ($isUpdate) {
            Site::where('user_id', $userId)
                ->whereNotIn('id', $processedSiteIds)
                ->get()
                ->each(function($site) {
                    $site->delete();
                });
        }
    }

    /**
     * Process accounts for site create/update
     */
    private function processAccounts($siteId, $accounts, $isUpdate = false)
    {
        $processedAccountIds = [];
        
        foreach ($accounts as $accountData) {
            $accountId = $accountData['id'] ?? null;
            
            $accountFields = [
                'site_id' => $siteId,
                'account_name' => $accountData['account_name'] ?? '',
                'account_number' => $accountData['account_number'] ?? '',
                'billing_date' => isset($accountData['billing_date']) && $accountData['billing_date'] !== '' ? (int) $accountData['billing_date'] : null,
                'tariff_template_id' => $accountData['tariff_template_id'] ?? null,
                'bill_day' => $accountData['bill_day'] ?? null,
                'read_day' => $accountData['read_day'] ?? null,
            ];
            
            if ($accountId && $isUpdate) {
                $account = Account::find($accountId);
                if ($account && $account->site_id == $siteId) {
                    $account->update($accountFields);
                } else {
                    $account = Account::create($accountFields);
                    $accountId = $account->id;
                }
            } else {
                $account = Account::create($accountFields);
                $accountId = $account->id;
            }
            
            $processedAccountIds[] = $accountId;
            
            // Process meters for this account
            if (isset($accountData['meters']) && is_array($accountData['meters'])) {
                $this->processMeters($accountId, $accountData['meters'], $isUpdate);
            }
        }
        
        // If updating, delete accounts not in the processed list
        if ($isUpdate) {
            Account::where('site_id', $siteId)
                ->whereNotIn('id', $processedAccountIds)
                ->get()
                ->each(function($account) {
                    $account->delete();
                });
        }
    }

    /**
     * Process meters for account create/update
     */
    private function processMeters($accountId, $meters, $isUpdate = false)
    {
        $processedMeterIds = [];
        
        foreach ($meters as $meterData) {
            $meterId = $meterData['id'] ?? null;
            
            $meterFields = [
                'account_id' => $accountId,
                'meter_type_id' => $meterData['meter_type_id'] ?? null,
                'meter_category_id' => $meterData['meter_category_id'] ?? self::DEFAULT_METER_CATEGORY_ID,
                'meter_title' => $meterData['meter_title'] ?? '',
                'meter_number' => $meterData['meter_number'] ?? '',
            ];
            
            if ($meterId && $isUpdate) {
                $meter = Meter::find($meterId);
                if ($meter && $meter->account_id == $accountId) {
                    $meter->update($meterFields);
                } else {
                    $meter = Meter::create($meterFields);
                    $meterId = $meter->id;
                }
            } else {
                $meter = Meter::create($meterFields);
                $meterId = $meter->id;
            }
            
            $processedMeterIds[] = $meterId;
            
            // Process sample reading if provided
            if (isset($meterData['sample_reading']) && !empty($meterData['sample_reading'])) {
                MeterReadings::create([
                    'meter_id' => $meterId,
                    'reading_date' => now()->format('Y-m-d'),
                    'reading_value' => $meterData['sample_reading'],
                ]);
            }
        }
        
        // If updating, delete meters not in the processed list
        if ($isUpdate) {
            Meter::where('account_id', $accountId)
                ->whereNotIn('id', $processedMeterIds)
                ->get()
                ->each(function($meter) {
                    $meter->delete();
                });
        }
    }
}
