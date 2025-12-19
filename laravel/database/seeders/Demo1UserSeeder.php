<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Seeder to create demo1 user with a Date-to-Date tariff template.
 * 
 * Creates:
 * - "Durban Date to Date" tariff template (billing_type: DATE_TO_DATE)
 * - demo1@mycities.co.za user
 * - Site, Account, Water Meter linked to the date-to-date tariff
 * - Initial meter reading for testing
 */
class Demo1UserSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('========================================');
        $this->command->info('  Demo1 User Seeder (Date-to-Date)');
        $this->command->info('========================================');

        // Get existing region (Durban)
        $region = DB::table('regions')->first();
        if (!$region) {
            $this->command->error('No region found. Please run the base seeders first.');
            return;
        }
        $regionId = $region->id;

        // Step 1: Create "Durban Date to Date" Tariff Template
        $this->command->info('[1/5] Creating "Durban Date to Date" Tariff Template...');
        
        // Check if it already exists
        $existingTariff = DB::table('regions_account_type_cost')
            ->where('template_name', 'Durban Date to Date')
            ->first();

        if ($existingTariff) {
            $tariffId = $existingTariff->id;
            // Update to include electricity
            DB::table('regions_account_type_cost')->where('id', $tariffId)->update([
                'is_electricity' => true,
                'electricity' => json_encode([
                    ['min' => 0, 'max' => 600, 'cost' => 1.50],    // R1.50/kWh for 0-600 kWh
                    ['min' => 600, 'max' => 99999, 'cost' => 2.20] // R2.20/kWh for 600+ kWh
                ]),
                'updated_at' => now(),
            ]);
            $this->command->info("   Tariff already exists (ID: $tariffId) - updated to include electricity");
        } else {
            $tariffId = DB::table('regions_account_type_cost')->insertGetId([
                'region_id' => $regionId,
                'template_name' => 'Durban Date to Date',
                'billing_type' => 'DATE_TO_DATE',  // Key setting!
                'is_water' => true,
                'is_electricity' => true,  // Now includes electricity!
                'vat_percentage' => 15.00,
                'billing_day' => 1,  // Not used for date-to-date
                'read_day' => 1,     // Not used for date-to-date
                'is_active' => true,
                'effective_from' => Carbon::now()->startOfYear(),
                'effective_to' => null,
                // Single tier: 0-10000 liters at R50 per kL
                'water_in' => json_encode([
                    [
                        'min' => 0,
                        'max' => 10000,
                        'cost' => 50.00  // R50 per kL
                    ]
                ]),
                'water_out' => json_encode([]),
                'electricity' => json_encode([
                    ['min' => 0, 'max' => 600, 'cost' => 1.50],    // R1.50/kWh for 0-600 kWh
                    ['min' => 600, 'max' => 99999, 'cost' => 2.20] // R2.20/kWh for 600+ kWh
                ]),
                'fixed_costs' => json_encode([]),
                'customer_costs' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: Durban Date to Date (ID: $tariffId)");
            $this->command->info("   - Billing Type: DATE_TO_DATE");
            $this->command->info("   - Water: 1 tier (0-10000L @ R50/kL)");
            $this->command->info("   - Electricity: 2 tiers (0-600kWh @ R1.50, 600+ @ R2.20)");
        }

        // Step 2: Create demo1 user
        $this->command->info('[2/5] Creating demo1@mycities.co.za user...');
        
        $existingUser = DB::table('users')->where('email', 'demo1@mycities.co.za')->first();
        
        if ($existingUser) {
            $userId = $existingUser->id;
            $this->command->info("   User already exists (ID: $userId)");
        } else {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Demo1 User',
                'email' => 'demo1@mycities.co.za',
                'password' => Hash::make('demo123'),
                'contact_number' => '0821234567',
                'email_verified_at' => now(),
                'is_admin' => 0,
                'is_demo' => 1,  // Mark as demo user
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: demo1@mycities.co.za (ID: $userId)");
        }

        // Step 3: Get or create meter types
        $this->command->info('[3/5] Setting up meter types...');
        
        $waterType = DB::table('meter_types')->where('title', 'Water')->first();
        if (!$waterType) {
            $waterTypeId = DB::table('meter_types')->insertGetId([
                'title' => 'Water',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $waterTypeId = $waterType->id;
        }

        $electricityType = DB::table('meter_types')->where('title', 'Electricity')->first();
        if (!$electricityType) {
            $electricityTypeId = DB::table('meter_types')->insertGetId([
                'title' => 'Electricity',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $electricityTypeId = $electricityType->id;
        }

        // meter_category_id is nullable, so we can skip it
        $waterInCatId = null;
        $this->command->info("   Water meter type ready (ID: $waterTypeId)");
        $this->command->info("   Electricity meter type ready (ID: $electricityTypeId)");

        // Step 4: Create Site and Account for demo1
        $this->command->info('[4/5] Creating Site and Account...');
        
        // Check for existing site for this user
        $existingSite = DB::table('sites')->where('user_id', $userId)->first();
        
        if ($existingSite) {
            $siteId = $existingSite->id;
            $this->command->info("   Site already exists (ID: $siteId)");
        } else {
            $siteId = DB::table('sites')->insertGetId([
                'user_id' => $userId,
                'region_id' => $regionId,
                'title' => '456 Ocean View Drive, Durban',
                'lat' => '-29.8500',
                'lng' => '31.0300',
                'address' => '456 Ocean View Drive, Umhlanga, KwaZulu-Natal',
                'email' => 'demo1@mycities.co.za',
                'billing_type' => 'date_to_date',  // Site billing type
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: Site (ID: $siteId)");
        }

        // Check for existing account for this site
        $existingAccount = DB::table('accounts')->where('site_id', $siteId)->first();
        
        if ($existingAccount) {
            $accountId = $existingAccount->id;
            // Update to use the date-to-date tariff
            DB::table('accounts')->where('id', $accountId)->update([
                'tariff_template_id' => $tariffId,
                'updated_at' => now(),
            ]);
            $this->command->info("   Account already exists (ID: $accountId) - updated tariff");
        } else {
            $accountId = DB::table('accounts')->insertGetId([
                'site_id' => $siteId,
                'tariff_template_id' => $tariffId,  // Link to Date-to-Date tariff!
                'account_name' => 'Demo1 Residence',
                'account_number' => 'D2D-2024-001',
                'bill_day' => null,  // Not applicable for date-to-date
                'read_day' => null,  // Not applicable for date-to-date
                'bill_read_day_active' => false,
                'water_email' => 'demo1@mycities.co.za',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: Account (ID: $accountId)");
        }

        // Step 5: Create Water Meter and Initial Readings
        $this->command->info('[5/6] Creating Water Meter and Initial Readings...');
        
        // Check for existing water meter
        $existingWaterMeter = DB::table('meters')
            ->where('account_id', $accountId)
            ->where('meter_type_id', $waterTypeId)
            ->first();
        
        if ($existingWaterMeter) {
            $waterMeterId = $existingWaterMeter->id;
            $this->command->info("   Water Meter already exists (ID: $waterMeterId)");
        } else {
            $waterMeterId = DB::table('meters')->insertGetId([
                'account_id' => $accountId,
                'meter_category_id' => $waterInCatId,
                'meter_type_id' => $waterTypeId,
                'meter_title' => 'Main Water Meter',
                'meter_number' => 'D2D-WM-001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: Water Meter (ID: $waterMeterId)");
        }

        // Create initial water readings for testing (if none exist)
        $waterReadingCount = DB::table('meter_readings')->where('meter_id', $waterMeterId)->count();
        
        if ($waterReadingCount == 0) {
            // Opening reading (start of billing period)
            DB::table('meter_readings')->insert([
                'meter_id' => $waterMeterId,
                'reading_value' => 5000,  // 5000 liters = 5 kL
                'reading_date' => Carbon::now()->subDays(30),
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Current reading (for testing calculation)
            DB::table('meter_readings')->insert([
                'meter_id' => $waterMeterId,
                'reading_value' => 8500,  // 8500 liters = 8.5 kL (3.5 kL consumed)
                'reading_date' => Carbon::now(),
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: 2 water readings (5000L -> 8500L)");
            $this->command->info("   Consumption: 3500L = 3.5 kL");
            $this->command->info("   Expected charge: 3.5 kL × R50 = R175.00 + VAT");
        } else {
            $this->command->info("   Water readings already exist ($waterReadingCount readings)");
        }

        // Step 6: Create Electricity Meter and Initial Readings
        $this->command->info('[6/6] Creating Electricity Meter and Initial Readings...');
        
        // Check for existing electricity meter
        $existingElecMeter = DB::table('meters')
            ->where('account_id', $accountId)
            ->where('meter_type_id', $electricityTypeId)
            ->first();
        
        if ($existingElecMeter) {
            $elecMeterId = $existingElecMeter->id;
            $this->command->info("   Electricity Meter already exists (ID: $elecMeterId)");
        } else {
            $elecMeterId = DB::table('meters')->insertGetId([
                'account_id' => $accountId,
                'meter_category_id' => null,
                'meter_type_id' => $electricityTypeId,
                'meter_title' => 'Main Electricity Meter',
                'meter_number' => 'D2D-EM-001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: Electricity Meter (ID: $elecMeterId)");
        }

        // Create initial electricity readings for testing (if none exist)
        $elecReadingCount = DB::table('meter_readings')->where('meter_id', $elecMeterId)->count();
        
        if ($elecReadingCount == 0) {
            // Opening reading (start of billing period)
            DB::table('meter_readings')->insert([
                'meter_id' => $elecMeterId,
                'reading_value' => 10000,  // 10000 kWh starting
                'reading_date' => Carbon::now()->subDays(30),
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Current reading (for testing calculation)
            DB::table('meter_readings')->insert([
                'meter_id' => $elecMeterId,
                'reading_value' => 10450,  // 450 kWh consumed
                'reading_date' => Carbon::now(),
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: 2 electricity readings (10000 -> 10450 kWh)");
            $this->command->info("   Consumption: 450 kWh");
            $this->command->info("   Expected charge: 450 × R1.50 = R675.00 + VAT");
        } else {
            $this->command->info("   Electricity readings already exist ($elecReadingCount readings)");
        }

        // Summary
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  Demo1 Setup Complete!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('  Email: demo1@mycities.co.za');
        $this->command->info('  Password: demo123');
        $this->command->info('');
        $this->command->info('Tariff Configuration:');
        $this->command->info('  Template: Durban Date to Date');
        $this->command->info('  Billing Type: DATE_TO_DATE');
        $this->command->info('  Water Tier: 0-10000L @ R50/kL');
        $this->command->info('  Electricity Tier 1: 0-600 kWh @ R1.50/kWh');
        $this->command->info('  Electricity Tier 2: 600+ kWh @ R2.20/kWh');
        $this->command->info('  VAT: 15%');
        $this->command->info('');
        $this->command->info('Test Calculations:');
        $this->command->info('  WATER (3500L = 3.5 kL consumption):');
        $this->command->info('    Charge: 3.5 × R50 = R175.00');
        $this->command->info('    VAT (15%): R26.25');
        $this->command->info('    Subtotal: R201.25');
        $this->command->info('');
        $this->command->info('  ELECTRICITY (450 kWh consumption):');
        $this->command->info('    Charge: 450 × R1.50 = R675.00');
        $this->command->info('    VAT (15%): R101.25');
        $this->command->info('    Subtotal: R776.25');
        $this->command->info('');
        $this->command->info('  TOTAL: R977.50');
        $this->command->info('');
    }
}

