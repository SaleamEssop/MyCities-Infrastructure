<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Meter;
use App\Models\MeterReadings;
use App\Models\MeterType;
use App\Models\Regions;
use App\Models\RegionsAccountTypeCost;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoUserSeeder - Creates a demo user with Durban region and DurbanTariff.
 * 
 * This seeder creates:
 * 1. Durban region (if not exists)
 * 2. DurbanTariff template with tiered water rates
 * 3. Demo user account
 * 4. Site linked to user
 * 5. Account linked to site and tariff
 * 6. Water and Electricity meters
 * 7. Sample meter readings for 3 billing periods
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating Demo User with Durban Tariff...');

        // 1. Create or get Durban region
        $region = Regions::firstOrCreate(
            ['name' => 'Durban'],
            [
                'water_email' => 'water@durban.gov.za',
                'electricity_email' => 'electricity@durban.gov.za',
            ]
        );
        $this->command->info("Region: {$region->name} (ID: {$region->id})");

        // 2. Create or update DurbanTariff template
        $tariff = RegionsAccountTypeCost::updateOrCreate(
            [
                'template_name' => 'DurbanTariff',
                'region_id' => $region->id,
            ],
            [
                'billing_type' => 'MONTHLY',
                'start_date' => '2025-01-01',
                'end_date' => '2026-12-31',
                'is_water' => true,
                'is_electricity' => true,
                'water_used' => 32, // 32 KL for demo
                'electricity_used' => 500, // 500 kWh for demo
                'billing_day' => 15,
                'read_day' => 10,
                'vat_percentage' => 15,
                'vat_rate' => 1200, // Municipal rates
                'rates_rebate' => 0,
                'ratable_value' => 0,
                'is_active' => true,
                
                // Water In tiers (Litres, Cost per KL)
                'water_in' => [
                    ['min' => '0', 'max' => '6000', 'cost' => '20'],
                    ['min' => '6000', 'max' => '15000', 'cost' => '30'],
                    ['min' => '15000', 'max' => '45000', 'cost' => '50'],
                    ['min' => '45000', 'max' => '100000', 'cost' => '70'],
                ],
                
                // Water In Additional charges
                'waterin_additional' => [
                    ['title' => 'Infrastructure Surcharge', 'percentage' => '100', 'cost' => '1.50'],
                ],
                
                // Water Out tiers
                'water_out' => [
                    ['min' => '0', 'max' => '100000', 'percentage' => '95', 'cost' => '15'],
                ],
                
                // Water Out Additional charges
                'waterout_additional' => [
                    ['title' => 'Sewage Disposal Charge', 'percentage' => '95', 'cost' => '1.50'],
                ],
                
                // Electricity tiers (kWh)
                'electricity' => [
                    ['min' => '0', 'max' => '50', 'cost' => '1.50'],
                    ['min' => '50', 'max' => '350', 'cost' => '2.00'],
                    ['min' => '350', 'max' => '600', 'cost' => '2.50'],
                    ['min' => '600', 'max' => '100000', 'cost' => '3.00'],
                ],
                
                // Electricity Additional charges
                'electricity_additional' => [],
                
                // Fixed costs (not editable by customer)
                'fixed_costs' => [
                    ['name' => 'Service Charge', 'value' => '150'],
                    ['name' => 'Meter Rental', 'value' => '50'],
                ],
                
                // Customer editable costs
                'customer_costs' => [
                    ['name' => 'Rates', 'value' => '1200'],
                    ['name' => 'Refuse Removal', 'value' => '350'],
                ],
            ]
        );
        $this->command->info("Tariff: {$tariff->template_name} (ID: {$tariff->id})");

        // 3. Create demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@mycities.co.za'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('demo123'),
                'contact_number' => '0821234567',
                'is_demo' => 1,  // Mark as demo user
            ]
        );
        $this->command->info("User: {$user->email} (ID: {$user->id})");

        // 4. Create site for user
        $site = Site::firstOrCreate(
            ['user_id' => $user->id],
            [
                'title' => 'Demo Home',
                'address' => '123 Demo Street, Durban, 4001',
                'region_id' => $region->id,
                'lat' => '-29.8587',
                'lng' => '31.0218',
            ]
        );
        $this->command->info("Site: {$site->title} (ID: {$site->id})");

        // 5. Create account linked to site and tariff
        $account = Account::firstOrCreate(
            ['site_id' => $site->id],
            [
                'tariff_template_id' => $tariff->id,
                'account_name' => 'Demo Account',
                'account_number' => 'ACC-DEMO-001',
                'billing_date' => 15,
                'bill_day' => 15,
                'read_day' => 10,
            ]
        );
        $this->command->info("Account: {$account->account_name} (ID: {$account->id})");

        // 6. Create meter types if not exist
        $waterType = MeterType::firstOrCreate(['title' => 'Water'], ['id' => 1]);
        $electricityType = MeterType::firstOrCreate(['title' => 'Electricity'], ['id' => 2]);

        // 7. Create water meter
        $waterMeter = Meter::firstOrCreate(
            ['account_id' => $account->id, 'meter_type_id' => $waterType->id],
            [
                'meter_title' => 'Water Meter',
                'meter_number' => 'WM-132452',
            ]
        );
        $this->command->info("Water Meter: {$waterMeter->meter_number} (ID: {$waterMeter->id})");

        // 8. Create electricity meter
        $electricityMeter = Meter::firstOrCreate(
            ['account_id' => $account->id, 'meter_type_id' => $electricityType->id],
            [
                'meter_title' => 'Electricity Meter',
                'meter_number' => 'EM-98736',
            ]
        );
        $this->command->info("Electricity Meter: {$electricityMeter->meter_number} (ID: {$electricityMeter->id})");

        // 9. Create sample meter readings for 3 periods
        $this->createSampleReadings($waterMeter, $electricityMeter);

        $this->command->info('');
        $this->command->info('=== Demo User Created Successfully ===');
        $this->command->info("Email: demo@mycities.co.za");
        $this->command->info("Password: demo123");
        $this->command->info("Region: Durban");
        $this->command->info("Tariff: DurbanTariff");
        $this->command->info('=====================================');
    }

    /**
     * Create sample meter readings for demo purposes.
     */
    private function createSampleReadings(Meter $waterMeter, Meter $electricityMeter): void
    {
        // Water readings (in kiloliters - stored as decimal)
        $waterReadings = [
            // Period 1: Feb 10 - Mar 10
            ['date' => '2025-02-10', 'value' => 75.20, 'type' => MeterReadings::TYPE_ACTUAL],
            ['date' => '2025-03-10', 'value' => 106.55, 'type' => MeterReadings::TYPE_FINAL_ACTUAL],
            
            // Period 2: Mar 10 - Apr 10
            ['date' => '2025-04-10', 'value' => 140.45, 'type' => MeterReadings::TYPE_FINAL_ACTUAL],
            
            // Period 3: Apr 10 - May 10 (current)
            ['date' => '2025-05-05', 'value' => 165.30, 'type' => MeterReadings::TYPE_ACTUAL],
        ];

        foreach ($waterReadings as $reading) {
            MeterReadings::updateOrCreate(
                [
                    'meter_id' => $waterMeter->id,
                    'reading_date' => $reading['date'],
                ],
                [
                    'reading_value' => $reading['value'],
                    'reading_type' => $reading['type'],
                    'is_locked' => $reading['type'] === MeterReadings::TYPE_FINAL_ACTUAL,
                ]
            );
        }
        $this->command->info("Created " . count($waterReadings) . " water readings");

        // Electricity readings (in kWh)
        $electricityReadings = [
            // Period 1: Feb 10 - Mar 10
            ['date' => '2025-02-10', 'value' => 761656, 'type' => MeterReadings::TYPE_ACTUAL],
            ['date' => '2025-03-10', 'value' => 762556, 'type' => MeterReadings::TYPE_FINAL_ACTUAL],
            
            // Period 2: Mar 10 - Apr 10
            ['date' => '2025-04-10', 'value' => 763456, 'type' => MeterReadings::TYPE_FINAL_ACTUAL],
            
            // Period 3: Apr 10 - May 10 (current)
            ['date' => '2025-05-05', 'value' => 764156, 'type' => MeterReadings::TYPE_ACTUAL],
        ];

        foreach ($electricityReadings as $reading) {
            MeterReadings::updateOrCreate(
                [
                    'meter_id' => $electricityMeter->id,
                    'reading_date' => $reading['date'],
                ],
                [
                    'reading_value' => $reading['value'],
                    'reading_type' => $reading['type'],
                    'is_locked' => $reading['type'] === MeterReadings::TYPE_FINAL_ACTUAL,
                ]
            );
        }
        $this->command->info("Created " . count($electricityReadings) . " electricity readings");
    }
}

