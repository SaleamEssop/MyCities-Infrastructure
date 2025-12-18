<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FullDataSeeder extends Seeder
{
    /**
     * Seed full test data: Regions -> Tariffs -> Users -> Sites -> Accounts -> Meters -> Readings
     */
    public function run()
    {
        $this->command->info('========================================');
        $this->command->info('  Full Data Seeder');
        $this->command->info('========================================');

        // Step 1: Create Meter Types
        $this->command->info('[1/8] Creating Meter Types...');
        $waterTypeId = DB::table('meter_types')->insertGetId([
            'title' => 'Water',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $electricityTypeId = DB::table('meter_types')->insertGetId([
            'title' => 'Electricity',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command->info("   Created: Water (ID: $waterTypeId), Electricity (ID: $electricityTypeId)");

        // Step 2: Create Meter Categories
        $this->command->info('[2/8] Creating Meter Categories...');
        $waterInCatId = DB::table('meter_categories')->insertGetId([
            'meter_type_id' => $waterTypeId,
            'title' => 'Water in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $waterOutCatId = DB::table('meter_categories')->insertGetId([
            'meter_type_id' => $waterTypeId,
            'title' => 'Water out',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $electricityCatId = DB::table('meter_categories')->insertGetId([
            'meter_type_id' => $electricityTypeId,
            'title' => 'Electricity',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command->info("   Created: Water in, Water out, Electricity");

        // Step 3: Create Region (Durban/eThekwini)
        $this->command->info('[3/8] Creating Region (Durban)...');
        $regionId = DB::table('regions')->insertGetId([
            'name' => 'Durban (eThekwini)',
            'electricity_base_unit_cost' => 2.2425,
            'electricity_base_unit' => 'kWh',
            'water_base_unit_cost' => 25.50,
            'water_base_unit' => 'kL',
            'water_email' => 'eservices@durban.gov.za',
            'electricity_email' => 'electricity@durban.gov.za',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command->info("   Created: Durban (eThekwini) (ID: $regionId)");

        // Step 4: Create Tariff Template (RegionsAccountTypeCost)
        $this->command->info('[4/8] Creating Tariff Template...');
        $tariffId = DB::table('regions_account_type_costs')->insertGetId([
            'region_id' => $regionId,
            'template_name' => 'Durban Residential Water Tariff 2024/25',
            'billing_type' => 'MONTHLY',
            'is_water' => true,
            'vat_rate' => 15.00,
            'billing_day' => 24,
            'read_day' => 5,
            'is_active' => true,
            'effective_from' => '2024-07-01',
            'effective_to' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command->info("   Created: Durban Residential Water Tariff (ID: $tariffId)");

        // Step 5: Create Tariff Tiers (Durban water rates)
        $this->command->info('[5/8] Creating Tariff Tiers...');
        $tiers = [
            ['tier_number' => 1, 'min_units' => 0, 'max_units' => 6000, 'rate_per_unit' => 25.50],      // 0-6 kL
            ['tier_number' => 2, 'min_units' => 6000, 'max_units' => 15000, 'rate_per_unit' => 32.80],  // 6-15 kL
            ['tier_number' => 3, 'min_units' => 15000, 'max_units' => 30000, 'rate_per_unit' => 42.50], // 15-30 kL
            ['tier_number' => 4, 'min_units' => 30000, 'max_units' => null, 'rate_per_unit' => 55.20],  // 30+ kL
        ];
        foreach ($tiers as $tier) {
            DB::table('tariff_tiers')->insert([
                'tariff_template_id' => $tariffId,
                'tier_number' => $tier['tier_number'],
                'min_units' => $tier['min_units'],
                'max_units' => $tier['max_units'],
                'rate_per_unit' => $tier['rate_per_unit'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command->info("   Created: 4 water tariff tiers");

        // Step 6: Create Users
        $this->command->info('[6/8] Creating Users...');
        
        // Admin user
        $adminId = DB::table('users')->insertGetId([
            'name' => 'Admin User',
            'email' => 'admin@mycities.local',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Test user
        $testUserId = DB::table('users')->insertGetId([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command->info("   Created: admin@mycities.local, john@example.com");

        // Step 7: Create Site, Account, Meters
        $this->command->info('[7/8] Creating Site, Account, Meters...');
        
        // Site
        $siteId = DB::table('sites')->insertGetId([
            'user_id' => $testUserId,
            'region_id' => $regionId,
            'title' => '123 Main Street, Durban',
            'lat' => '-29.8587',
            'lng' => '31.0218',
            'address' => '123 Main Street, Durban North, KwaZulu-Natal',
            'email' => 'john@example.com',
            'billing_type' => 'month_to_month',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Account
        $accountId = DB::table('accounts')->insertGetId([
            'site_id' => $siteId,
            'tariff_template_id' => $tariffId,
            'account_name' => 'Smith Residence',
            'account_number' => 'ACC-2024-001',
            'bill_day' => 24,
            'read_day' => 5,
            'bill_read_day_active' => true,
            'water_email' => 'eservices@durban.gov.za',
            'electricity_email' => 'electricity@durban.gov.za',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Water In Meter
        $waterMeterId = DB::table('meters')->insertGetId([
            'account_id' => $accountId,
            'meter_category_id' => $waterInCatId,
            'meter_type_id' => $waterTypeId,
            'meter_title' => 'Main Water Meter',
            'meter_number' => 'WM-12345678',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Electricity Meter
        $elecMeterId = DB::table('meters')->insertGetId([
            'account_id' => $accountId,
            'meter_category_id' => $electricityCatId,
            'meter_type_id' => $electricityTypeId,
            'meter_title' => 'Main Electricity Meter',
            'meter_number' => 'EM-87654321',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->command->info("   Created: Site, Account, 2 Meters");

        // Step 8: Create Meter Readings (last 3 months)
        $this->command->info('[8/8] Creating Meter Readings...');
        
        // Water readings (in liters, increasing)
        $waterReadings = [
            ['date' => Carbon::now()->subMonths(3), 'value' => 10000],
            ['date' => Carbon::now()->subMonths(2), 'value' => 18500],
            ['date' => Carbon::now()->subMonths(1), 'value' => 27200],
            ['date' => Carbon::now()->subDays(5), 'value' => 35800],
        ];
        foreach ($waterReadings as $reading) {
            DB::table('meter_readings')->insert([
                'meter_id' => $waterMeterId,
                'reading_value' => $reading['value'],
                'reading_date' => $reading['date'],
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Electricity readings (in kWh)
        $elecReadings = [
            ['date' => Carbon::now()->subMonths(3), 'value' => 45000],
            ['date' => Carbon::now()->subMonths(2), 'value' => 45850],
            ['date' => Carbon::now()->subMonths(1), 'value' => 46720],
            ['date' => Carbon::now()->subDays(5), 'value' => 47580],
        ];
        foreach ($elecReadings as $reading) {
            DB::table('meter_readings')->insert([
                'meter_id' => $elecMeterId,
                'reading_value' => $reading['value'],
                'reading_date' => $reading['date'],
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command->info("   Created: 8 meter readings (4 water, 4 electricity)");

        // Summary
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  Setup Complete!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Test User Login:');
        $this->command->info('  Email: john@example.com');
        $this->command->info('  Password: password123');
        $this->command->info('');
        $this->command->info('Admin Login:');
        $this->command->info('  Email: admin@mycities.local');
        $this->command->info('  Password: admin123');
        $this->command->info('');
    }
}




