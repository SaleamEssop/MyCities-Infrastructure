<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Meter;
use App\Models\MeterReadings;
use App\Models\MeterType;
use App\Models\Regions;
use App\Models\RegionsAccountTypeCost;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserAccountSetupController extends Controller
{
    // Default meter category ID
    private const DEFAULT_METER_CATEGORY_ID = 1;
    
    // Test user credentials
    private const TEST_USER_PASSWORD = 'demo123';
    
    // Test user meter reading simulation constants
    private const MIN_MONTHLY_WATER_USAGE_LITERS = 15000;
    private const MAX_MONTHLY_WATER_USAGE_LITERS = 25000;
    private const MIN_MONTHLY_ELECTRICITY_USAGE_KWH = 300;
    private const MAX_MONTHLY_ELECTRICITY_USAGE_KWH = 500;

    /**
     * Display the setup wizard page
     */
    public function index()
    {
        $regions = Regions::all();
        $meterTypes = MeterType::all();
        
        return view('admin.user-accounts.setup', [
            'regions' => $regions,
            'meterTypes' => $meterTypes,
        ]);
    }

    /**
     * Get tariff templates filtered by region
     */
    public function getTariffTemplatesByRegion($regionId)
    {
        $templates = RegionsAccountTypeCost::where('region_id', $regionId)
            ->where('is_active', 1)
            ->get(['id', 'template_name', 'is_water', 'is_electricity', 'start_date', 'end_date']);
        
        return response()->json([
            'status' => 200,
            'data' => $templates
        ]);
    }

    /**
     * Validate email uniqueness via AJAX
     */
    public function validateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        
        $exists = User::where('email', $request->email)->exists();
        
        return response()->json([
            'exists' => $exists
        ]);
    }

    /**
     * Validate phone number uniqueness via AJAX
     */
    public function validatePhone(Request $request)
    {
        $request->validate([
            'contact_number' => 'required|string'
        ]);
        
        $exists = User::where('contact_number', $request->contact_number)->exists();
        
        return response()->json([
            'exists' => $exists
        ]);
    }

    /**
     * Store only user details (without region, tariff, account, meters)
     */
    public function storeUserOnly(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contact_number' => 'required|string|max:20|unique:users,contact_number',
            'password' => 'required|string|min:6',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'password' => Hash::make($request->password),
                'is_admin' => 0,
            ]);
            
            return response()->json([
                'status' => 200, 
                'message' => 'User created successfully. You can add region, account, and meters later from the mobile app.',
                'user_id' => $user->id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new user with all related data through the wizard
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contact_number' => 'required|string|max:20|unique:users,contact_number',
            'password' => 'required|string|min:6',
            'region_id' => 'required|exists:regions,id',
            'tariff_template_id' => 'required|exists:regions_account_type_cost,id',
        ]);

        DB::beginTransaction();
        
        try {
            // Step 1: Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'password' => Hash::make($request->password),
                'is_admin' => 0,
            ]);
            
            // Step 2 & 3: Create site with region
            $site = Site::create([
                'user_id' => $user->id,
                'title' => $request->site_title ?? ($request->name . "'s Site"),
                'lat' => $request->lat ?? 0,
                'lng' => $request->lng ?? 0,
                'address' => $request->address ?? '',
                'email' => $request->email,
                'region_id' => $request->region_id,
                'billing_type' => $request->billing_type ?? 'monthly',
                'site_username' => $request->site_username ?? null,
            ]);
            
            // Step 4: Create account - check if tariff_template_id column exists
            $accountData = [
                'site_id' => $site->id,
                'account_name' => $request->account_name ?? ($request->name . "'s Account"),
                'account_number' => $request->account_number ?? ('ACC-' . time()),
                'billing_date' => $request->billing_date ?? null,
                'bill_day' => $request->bill_day ?? null,
                'read_day' => $request->read_day ?? null,
            ];
            
            // Only include tariff_template_id if the column exists
            if (Schema::hasColumn('accounts', 'tariff_template_id')) {
                $accountData['tariff_template_id'] = $request->tariff_template_id;
            }
            
            $account = Account::create($accountData);
            
            // Create meters if provided
            if ($request->has('meters') && is_array($request->meters)) {
                foreach ($request->meters as $meterData) {
                    $meter = Meter::create([
                        'account_id' => $account->id,
                        'meter_type_id' => $meterData['meter_type_id'] ?? null,
                        'meter_category_id' => $meterData['meter_category_id'] ?? self::DEFAULT_METER_CATEGORY_ID,
                        'meter_title' => $meterData['meter_title'] ?? '',
                        'meter_number' => $meterData['meter_number'] ?? '',
                    ]);
                    
                    // Add initial reading if provided - use read_day from account if no specific date
                    if (!empty($meterData['initial_reading'])) {
                        $readingDate = now()->format('Y-m-d');
                        // If read_day is set in account, use it to calculate reading date
                        if ($request->read_day) {
                            $currentDate = now();
                            $dayOfMonth = min((int)$request->read_day, $currentDate->daysInMonth);
                            $readingDate = $currentDate->copy()->setDay($dayOfMonth)->format('Y-m-d');
                        }
                        
                        MeterReadings::create([
                            'meter_id' => $meter->id,
                            'reading_date' => $readingDate,
                            'reading_value' => $meterData['initial_reading'],
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 200, 
                'message' => 'User account created successfully',
                'user_id' => $user->id,
                'account_id' => $account->id
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500, 
                'message' => 'Error creating user account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tariff template details (fixed costs, customer costs)
     */
    public function getTariffDetails($tariffId)
    {
        $tariff = RegionsAccountTypeCost::find($tariffId);
        
        if (!$tariff) {
            return response()->json(['status' => 404, 'message' => 'Tariff not found']);
        }
        
        return response()->json([
            'status' => 200,
            'data' => [
                'id' => $tariff->id,
                'template_name' => $tariff->template_name,
                'fixed_costs' => $tariff->fixed_costs ?? [],
                'customer_costs' => $tariff->customer_costs ?? [],
                'is_water' => (bool) $tariff->is_water,
                'is_electricity' => (bool) $tariff->is_electricity,
                'billing_type' => $tariff->billing_type ?? 'MONTHLY',
            ]
        ]);
    }

    /**
     * Create a test user with full demo data
     * Uses form data if provided, otherwise generates random values
     * Options: seed_months (3-6), create_new_region, create_new_tariff
     */
    public function createTestUser(Request $request)
    {
        // Get seed months (default 6, 0 = no test data)
        $seedMonths = (int) ($request->seed_months ?? 6);
        $seedMonths = max(0, min(6, $seedMonths)); // Clamp between 0-6
        
        // Generate short random number (3 digits)
        $randomNum = rand(100, 999);
        
        // Use form data if provided, otherwise generate defaults
        $useFormData = $request->use_form_data == '1';
        
        // Email
        if ($useFormData && !empty($request->form_email)) {
            $testEmail = $request->form_email;
            // Check if exists
            if (User::where('email', $testEmail)->exists()) {
                return redirect()->back()->with('error', "Email {$testEmail} already exists. Please use a different email.");
            }
        } else {
            $testEmail = 'demo' . $randomNum . '@mycities.co.za';
            while (User::where('email', $testEmail)->exists()) {
                $randomNum = rand(100, 999);
                $testEmail = 'demo' . $randomNum . '@mycities.co.za';
            }
        }
        
        // Phone
        $phone = ($useFormData && !empty($request->form_phone)) 
            ? $request->form_phone 
            : '084' . rand(1000000, 9999999);
        
        // Name
        $userName = ($useFormData && !empty($request->form_name)) 
            ? $request->form_name 
            : 'Demo User ' . $randomNum;
        
        // Password
        $password = ($useFormData && !empty($request->form_password)) 
            ? $request->form_password 
            : self::TEST_USER_PASSWORD;
        
        DB::beginTransaction();
        
        try {
            // Handle Region
            if ($request->has('create_new_region') && $request->create_new_region) {
                $region = Regions::create([
                    'name' => 'Test Region ' . $randomNum,
                    'water_email' => 'water@testregion.com',
                    'electricity_email' => 'electricity@testregion.com',
                ]);
            } elseif ($useFormData && !empty($request->form_region_id)) {
                $region = Regions::find($request->form_region_id);
                if (!$region) {
                    throw new \Exception('Selected region not found');
                }
            } else {
                $region = Regions::first();
                if (!$region) {
                    $region = Regions::create([
                        'name' => 'Durban (eThekwini)',
                        'water_email' => 'eservices@durban.gov.za',
                        'electricity_email' => 'electricity@durban.gov.za',
                    ]);
                }
            }
            
            // Handle Tariff Template
            if ($request->has('create_new_tariff') && $request->create_new_tariff) {
                $tariffTemplate = $this->createTestTariffTemplate($region->id, $randomNum);
            } elseif ($useFormData && !empty($request->form_tariff_id)) {
                $tariffTemplate = RegionsAccountTypeCost::find($request->form_tariff_id);
                if (!$tariffTemplate) {
                    throw new \Exception('Selected tariff not found');
                }
            } else {
                $tariffTemplate = RegionsAccountTypeCost::where('region_id', $region->id)->first();
                if (!$tariffTemplate) {
                    $tariffTemplate = $this->createTestTariffTemplate($region->id, $randomNum);
                }
            }
            
            // Ensure meter types exist
            $this->ensureMeterTypesExist();
            
            // Create user
            $user = User::create([
                'name' => $userName,
                'email' => $testEmail,
                'password' => Hash::make($password),
                'contact_number' => $phone,
            ]);
            
            // Site title and address
            $siteTitle = ($useFormData && !empty($request->form_site_title)) 
                ? $request->form_site_title 
                : 'Demo Site ' . $randomNum;
            $address = ($useFormData && !empty($request->form_address)) 
                ? $request->form_address 
                : '123 Test Street, Durban, 4001';
            
            // Create site
            $site = Site::create([
                'user_id' => $user->id,
                'title' => $siteTitle,
                'address' => $address,
                'lat' => -29.8587,
                'lng' => 31.0218,
                'email' => $testEmail,
                'region_id' => $region->id,
            ]);
            
            // Account details
            $accountName = ($useFormData && !empty($request->form_account_name)) 
                ? $request->form_account_name 
                : 'Demo Account';
            $accountNumber = ($useFormData && !empty($request->form_account_number)) 
                ? $request->form_account_number 
                : 'ACC' . $randomNum;
            
            // Create account
            $accountData = [
                'site_id' => $site->id,
                'account_name' => $accountName,
                'account_number' => $accountNumber,
            ];
            
            if ($tariffTemplate && Schema::hasColumn('accounts', 'tariff_template_id')) {
                $accountData['tariff_template_id'] = $tariffTemplate->id;
            }
            
            $account = Account::create($accountData);
            
            // Get meter types
            $waterMeterType = MeterType::where('title', 'Water')->first();
            $elecMeterType = MeterType::where('title', 'Electricity')->first();
            
            // Water meter
            $waterMeterNum = ($useFormData && !empty($request->form_water_meter)) 
                ? $request->form_water_meter 
                : 'WM' . rand(10000, 99999);
            $waterInitial = ($useFormData && !empty($request->form_water_reading)) 
                ? (int) $request->form_water_reading 
                : 1000;
            
            $waterMeter = Meter::create([
                'account_id' => $account->id,
                'meter_number' => $waterMeterNum,
                'meter_title' => 'Water Meter',
                'meter_type_id' => $waterMeterType->id ?? 1,
                'meter_category_id' => self::DEFAULT_METER_CATEGORY_ID,
            ]);
            
            // Electricity meter
            $elecMeterNum = ($useFormData && !empty($request->form_elec_meter)) 
                ? $request->form_elec_meter 
                : 'EM' . rand(10000, 99999);
            $elecInitial = ($useFormData && !empty($request->form_elec_reading)) 
                ? (int) $request->form_elec_reading 
                : 50000;
            
            $elecMeter = Meter::create([
                'account_id' => $account->id,
                'meter_number' => $elecMeterNum,
                'meter_title' => 'Electricity Meter',
                'meter_type_id' => $elecMeterType->id ?? 2,
                'meter_category_id' => self::DEFAULT_METER_CATEGORY_ID,
            ]);
            
            // Create sample readings for selected months (skip if 0)
            if ($seedMonths > 0) {
                $waterReading = $waterInitial;
                $elecReading = $elecInitial;
                
                for ($i = $seedMonths; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    
                    // Water reading
                    MeterReadings::create([
                        'meter_id' => $waterMeter->id,
                        'reading_value' => $waterReading,
                        'reading_date' => $date,
                    ]);
                    $waterReading += rand(self::MIN_MONTHLY_WATER_USAGE_LITERS, self::MAX_MONTHLY_WATER_USAGE_LITERS);
                    
                    // Electricity reading
                    MeterReadings::create([
                        'meter_id' => $elecMeter->id,
                        'reading_value' => $elecReading,
                        'reading_date' => $date,
                    ]);
                    $elecReading += rand(self::MIN_MONTHLY_ELECTRICITY_USAGE_KWH, self::MAX_MONTHLY_ELECTRICITY_USAGE_KWH);
                }
            }
            
            DB::commit();
            
            $message = "User created successfully!\n";
            $message .= "Email: {$testEmail}\n";
            $message .= "Password: {$password}\n";
            $message .= "Phone: {$phone}\n";
            $message .= "Region: {$region->name}\n";
            $message .= "Tariff: {$tariffTemplate->template_name}\n";
            $message .= $seedMonths > 0 
                ? "Seed Data: {$seedMonths} months of readings" 
                : "No test data - ready for real usage";
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error creating test user: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a test tariff template with water tiers
     */
    private function createTestTariffTemplate($regionId, $randomNum)
    {
        $tariff = RegionsAccountTypeCost::create([
            'region_id' => $regionId,
            'template_name' => 'Demo Tariff ' . $randomNum,
            'is_water' => true,
            'is_active' => true,
        ]);
        
        // Create water tiers if tariff_tiers table exists
        if (Schema::hasTable('tariff_tiers')) {
            $tiers = [
                ['tier_number' => 1, 'min_units' => 0, 'max_units' => 6000, 'rate_per_unit' => 25.50],
                ['tier_number' => 2, 'min_units' => 6000, 'max_units' => 15000, 'rate_per_unit' => 32.80],
                ['tier_number' => 3, 'min_units' => 15000, 'max_units' => 30000, 'rate_per_unit' => 42.50],
                ['tier_number' => 4, 'min_units' => 30000, 'max_units' => null, 'rate_per_unit' => 55.20],
            ];
            
            foreach ($tiers as $tier) {
                DB::table('tariff_tiers')->insert([
                    'tariff_template_id' => $tariff->id,
                    'tier_number' => $tier['tier_number'],
                    'min_units' => $tier['min_units'],
                    'max_units' => $tier['max_units'],
                    'rate_per_unit' => $tier['rate_per_unit'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        return $tariff;
    }
    
    /**
     * Ensure meter types exist in the database
     */
    private function ensureMeterTypesExist()
    {
        if (!MeterType::where('title', 'Water')->exists()) {
            MeterType::create(['title' => 'Water']);
        }
        if (!MeterType::where('title', 'Electricity')->exists()) {
            MeterType::create(['title' => 'Electricity']);
        }
        
        // Also ensure meter categories exist
        if (Schema::hasTable('meter_categories')) {
            if (!DB::table('meter_categories')->where('name', 'Water in')->exists()) {
                $data = ['name' => 'Water in', 'created_at' => now(), 'updated_at' => now()];
                // Add meter_type_id if column exists
                if (Schema::hasColumn('meter_categories', 'meter_type_id')) {
                    $waterType = MeterType::where('title', 'Water')->first();
                    if ($waterType) $data['meter_type_id'] = $waterType->id;
                }
                DB::table('meter_categories')->insert($data);
            }
            if (!DB::table('meter_categories')->where('name', 'Electricity')->exists()) {
                $data = ['name' => 'Electricity', 'created_at' => now(), 'updated_at' => now()];
                // Add meter_type_id if column exists
                if (Schema::hasColumn('meter_categories', 'meter_type_id')) {
                    $elecType = MeterType::where('title', 'Electricity')->first();
                    if ($elecType) $data['meter_type_id'] = $elecType->id;
                }
                DB::table('meter_categories')->insert($data);
            }
        }
    }
}
