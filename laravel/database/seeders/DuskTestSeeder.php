<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Regions;
use App\Models\AccountType;
use App\Models\Site;
use App\Models\Account;
use App\Models\Meter;

/**
 * Seeder for Dusk browser tests.
 * Creates all necessary test data for comprehensive admin panel testing.
 */
class DuskTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create test admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('password'),
            ]
        );

        // Create additional test user for CRUD tests
        $testUser = User::firstOrCreate(
            ['email' => 'testuser@example.com'],
            [
                'name' => 'Test User',
                'contact_number' => '0123456789',
                'password' => Hash::make('password123'),
            ]
        );

        // Create test regions
        $region = Regions::firstOrCreate(
            ['name' => 'Test Region'],
            [
                'electricity_base_unit_cost' => 1.50,
                'electricity_base_unit' => 'kWh',
                'water_base_unit_cost' => 25.00,
                'water_base_unit' => 'kL',
                'water_email' => 'water@test.com',
                'electricity_email' => 'electricity@test.com',
            ]
        );

        // Create test account types
        $accountType = AccountType::firstOrCreate(
            ['type' => 'Residential'],
            ['is_active' => 1]
        );

        AccountType::firstOrCreate(
            ['type' => 'Commercial'],
            ['is_active' => 1]
        );

        // Create meter types if they don't exist
        $waterMeterType = DB::table('meter_types')->where('title', 'Water')->first();
        if (!$waterMeterType) {
            $waterMeterTypeId = DB::table('meter_types')->insertGetId([
                'title' => 'Water',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $waterMeterTypeId = $waterMeterType->id;
        }
        
        $electricityMeterType = DB::table('meter_types')->where('title', 'Electricity')->first();
        if (!$electricityMeterType) {
            $electricityMeterTypeId = DB::table('meter_types')->insertGetId([
                'title' => 'Electricity',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $electricityMeterTypeId = $electricityMeterType->id;
        }

        // Create meter categories if they don't exist
        $meterCategory = DB::table('meter_categories')->where('name', 'Standard')->first();
        if (!$meterCategory) {
            $meterCategoryId = DB::table('meter_categories')->insertGetId([
                'name' => 'Standard',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $meterCategoryId = $meterCategory->id;
        }

        // Create test site
        $site = Site::firstOrCreate(
            ['title' => 'Test Site'],
            [
                'user_id' => $testUser->id,
                'lat' => '-26.2041',
                'lng' => '28.0473',
                'address' => '123 Test Street, Johannesburg',
                'email' => 'testsite@example.com',
                'region_id' => $region->id,
                'billing_type' => 'date_to_date',
                'site_username' => 'test_site',
            ]
        );

        // Create test account
        $account = Account::firstOrCreate(
            ['account_number' => 'TEST-001'],
            [
                'site_id' => $site->id,
                'account_name' => 'Test Account',
                'billing_date' => 15,
                'region_id' => $region->id,
                'account_type_id' => $accountType->id,
            ]
        );

        // Create test meters
        $waterMeter = Meter::where('meter_number', 'WM-001')->first();
        if (!$waterMeter) {
            Meter::create([
                'account_id' => $account->id,
                'meter_category_id' => $meterCategoryId,
                'meter_type_id' => $waterMeterTypeId,
                'meter_title' => 'Water Meter 1',
                'meter_number' => 'WM-001',
            ]);
        }

        $electricityMeter = Meter::where('meter_number', 'EM-001')->first();
        if (!$electricityMeter) {
            Meter::create([
                'account_id' => $account->id,
                'meter_category_id' => $meterCategoryId,
                'meter_type_id' => $electricityMeterTypeId,
                'meter_title' => 'Electricity Meter 1',
                'meter_number' => 'EM-001',
            ]);
        }
    }
}
