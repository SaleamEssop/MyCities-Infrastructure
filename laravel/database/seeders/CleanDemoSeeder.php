<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Clean Demo Seeder - Creates completely isolated demo users with unique data
 * 
 * USER 1: demo@mycities.co.za (Monthly Billing)
 * USER 2: demo1@mycities.co.za (Date-to-Date Billing)
 * 
 * Each user has:
 * - Unique site
 * - Unique account with different tariff template
 * - Unique water meter with unique meter number
 * - Unique electricity meter with unique meter number
 * - Unique meter readings
 * - Unique payments
 */
class CleanDemoSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║         CLEAN DEMO SEEDER - ISOLATED USER DATA                ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->info('');

        // Get or create region
        $region = DB::table('regions')->first();
        if (!$region) {
            $regionId = DB::table('regions')->insertGetId([
                'name' => 'Durban (eThekwini)',
                'electricity_base_unit_cost' => 2.50,
                'electricity_base_unit' => 'kWh',
                'water_base_unit_cost' => 30.00,
                'water_base_unit' => 'kL',
                'water_email' => 'water@ethekwini.gov.za',
                'electricity_email' => 'electricity@ethekwini.gov.za',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $regionId = $region->id;
        }

        // Ensure meter types exist
        $waterTypeId = $this->ensureMeterType('Water');
        $electricityTypeId = $this->ensureMeterType('Electricity');

        // ═══════════════════════════════════════════════════════════════
        // USER 1: demo@mycities.co.za - MONTHLY BILLING
        // ═══════════════════════════════════════════════════════════════
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────────────────────────────┐');
        $this->command->info('│  USER 1: demo@mycities.co.za (MONTHLY BILLING)                  │');
        $this->command->info('└─────────────────────────────────────────────────────────────────┘');

        // Create/update user
        $user1Id = $this->ensureUser('demo@mycities.co.za', 'Demo User', 'demo123', '0821111111');

        // Create tariff template (Monthly)
        $tariff1Id = $this->ensureTariff('Durban Monthly Standard', $regionId, 'MONTHLY', [
            'water_in' => [
                ['min' => 0, 'max' => 6000, 'cost' => 35.00],      // 0-6 kL @ R35/kL
                ['min' => 6000, 'max' => 20000, 'cost' => 45.00], // 6-20 kL @ R45/kL
                ['min' => 20000, 'max' => 99999, 'cost' => 55.00] // 20+ kL @ R55/kL
            ],
            'electricity' => [
                ['min' => 0, 'max' => 500, 'cost' => 1.80],       // 0-500 kWh @ R1.80
                ['min' => 500, 'max' => 1000, 'cost' => 2.30],    // 500-1000 kWh @ R2.30
                ['min' => 1000, 'max' => 99999, 'cost' => 2.80]   // 1000+ kWh @ R2.80
            ],
            'vat' => 15
        ]);

        // Create site
        $site1Id = $this->createSite($user1Id, $regionId, '123 Main Street, Durban North', 'monthly');

        // Create account
        $account1Id = $this->createAccount($site1Id, $tariff1Id, 'Demo Home Account', 'MTH-2025-001');

        // Create Water Meter for User 1
        $waterMeter1Id = $this->createMeter($account1Id, $waterTypeId, 'Water Meter - Main House', 'WM-DEMO-001');

        // Create Electricity Meter for User 1
        $elecMeter1Id = $this->createMeter($account1Id, $electricityTypeId, 'Electricity Meter - Main DB', 'EM-DEMO-001');

        // Add water readings for User 1 (Monthly pattern)
        $this->addReadings($waterMeter1Id, [
            ['date' => '2025-01-10', 'value' => 100000],  // 100 kL starting
            ['date' => '2025-02-10', 'value' => 108500],  // 8.5 kL used
            ['date' => '2025-03-10', 'value' => 116200],  // 7.7 kL used
            ['date' => '2025-04-10', 'value' => 125800],  // 9.6 kL used
            ['date' => '2025-05-10', 'value' => 133400],  // 7.6 kL used (current)
        ]);

        // Add electricity readings for User 1 (Monthly pattern)
        $this->addReadings($elecMeter1Id, [
            ['date' => '2025-01-10', 'value' => 50000],   // 50000 kWh starting
            ['date' => '2025-02-10', 'value' => 50650],   // 650 kWh used
            ['date' => '2025-03-10', 'value' => 51280],   // 630 kWh used
            ['date' => '2025-04-10', 'value' => 51950],   // 670 kWh used
            ['date' => '2025-05-10', 'value' => 52580],   // 630 kWh used (current)
        ]);

        // Add payments for User 1
        $this->addPayments($account1Id, [
            ['date' => '2025-02-15', 'amount' => 850.00, 'method' => 'EFT', 'ref' => 'DEMO-PAY-001'],
            ['date' => '2025-03-15', 'amount' => 920.00, 'method' => 'EFT', 'ref' => 'DEMO-PAY-002'],
            ['date' => '2025-04-15', 'amount' => 1050.00, 'method' => 'Card', 'ref' => 'DEMO-PAY-003'],
        ]);

        $this->command->info("   ✓ User 1 setup complete");
        $this->command->info("     - Account: MTH-2025-001");
        $this->command->info("     - Water Meter: WM-DEMO-001");
        $this->command->info("     - Electricity Meter: EM-DEMO-001");

        // ═══════════════════════════════════════════════════════════════
        // USER 2: demo1@mycities.co.za - DATE-TO-DATE BILLING
        // ═══════════════════════════════════════════════════════════════
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────────────────────────────┐');
        $this->command->info('│  USER 2: demo1@mycities.co.za (DATE-TO-DATE BILLING)            │');
        $this->command->info('└─────────────────────────────────────────────────────────────────┘');

        // Create/update user
        $user2Id = $this->ensureUser('demo1@mycities.co.za', 'Demo1 User', 'demo123', '0822222222');

        // Create tariff template (Date-to-Date)
        $tariff2Id = $this->ensureTariff('Durban Date-to-Date', $regionId, 'DATE_TO_DATE', [
            'water_in' => [
                ['min' => 0, 'max' => 10000, 'cost' => 50.00],    // Simple: 0-10 kL @ R50/kL
            ],
            'electricity' => [
                ['min' => 0, 'max' => 600, 'cost' => 1.50],       // 0-600 kWh @ R1.50
                ['min' => 600, 'max' => 99999, 'cost' => 2.20],   // 600+ kWh @ R2.20
            ],
            'vat' => 15
        ]);

        // Create site
        $site2Id = $this->createSite($user2Id, $regionId, '456 Ocean View Drive, Umhlanga', 'date_to_date');

        // Create account
        $account2Id = $this->createAccount($site2Id, $tariff2Id, 'Demo1 Residence', 'D2D-2025-001');

        // Create Water Meter for User 2
        $waterMeter2Id = $this->createMeter($account2Id, $waterTypeId, 'Water Meter - Villa', 'WM-D2D-001');

        // Create Electricity Meter for User 2
        $elecMeter2Id = $this->createMeter($account2Id, $electricityTypeId, 'Electricity Meter - Villa', 'EM-D2D-001');

        // Add water readings for User 2 (Date-to-Date pattern)
        $this->addReadings($waterMeter2Id, [
            ['date' => '2025-03-10', 'value' => 5000],    // 5 kL starting
            ['date' => '2025-05-15', 'value' => 7450],    // 2.45 kL used
            ['date' => '2025-06-15', 'value' => 9050],    // 1.6 kL used
            ['date' => '2025-07-20', 'value' => 11050],   // 2.0 kL used (current period)
        ]);

        // Add electricity readings for User 2 (Date-to-Date pattern)
        $this->addReadings($elecMeter2Id, [
            ['date' => '2025-03-10', 'value' => 10000],   // Starting
            ['date' => '2025-05-15', 'value' => 10320],   // 320 kWh used
            ['date' => '2025-06-15', 'value' => 10680],   // 360 kWh used
            ['date' => '2025-07-20', 'value' => 11130],   // 450 kWh used (current period)
        ]);

        // Add payments for User 2
        $this->addPayments($account2Id, [
            ['date' => '2025-05-12', 'amount' => 500.00, 'method' => 'EFT', 'ref' => 'D2D-PAY-001'],
            ['date' => '2025-06-18', 'amount' => 400.00, 'method' => 'Cash', 'ref' => 'D2D-PAY-002'],
        ]);

        $this->command->info("   ✓ User 2 setup complete");
        $this->command->info("     - Account: D2D-2025-001");
        $this->command->info("     - Water Meter: WM-D2D-001");
        $this->command->info("     - Electricity Meter: EM-D2D-001");

        // ═══════════════════════════════════════════════════════════════
        // SUMMARY
        // ═══════════════════════════════════════════════════════════════
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║                      SEED COMPLETE                            ║');
        $this->command->info('╠═══════════════════════════════════════════════════════════════╣');
        $this->command->info('║                                                               ║');
        $this->command->info('║  USER 1: demo@mycities.co.za / demo123                        ║');
        $this->command->info('║    - Billing: MONTHLY                                         ║');
        $this->command->info('║    - Account: MTH-2025-001                                    ║');
        $this->command->info('║    - Water: WM-DEMO-001 | Electricity: EM-DEMO-001            ║');
        $this->command->info('║                                                               ║');
        $this->command->info('║  USER 2: demo1@mycities.co.za / demo123                       ║');
        $this->command->info('║    - Billing: DATE-TO-DATE                                    ║');
        $this->command->info('║    - Account: D2D-2025-001                                    ║');
        $this->command->info('║    - Water: WM-D2D-001 | Electricity: EM-D2D-001              ║');
        $this->command->info('║                                                               ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->info('');
    }

    private function ensureMeterType(string $title): int
    {
        $type = DB::table('meter_types')->where('title', $title)->first();
        if ($type) {
            return $type->id;
        }
        return DB::table('meter_types')->insertGetId([
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureUser(string $email, string $name, string $password, string $phone): int
    {
        $user = DB::table('users')->where('email', $email)->first();
        if ($user) {
            return $user->id;
        }
        return DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'contact_number' => $phone,
            'email_verified_at' => now(),
            'is_admin' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureTariff(string $name, int $regionId, string $billingType, array $rates): int
    {
        $tariff = DB::table('regions_account_type_cost')->where('template_name', $name)->first();
        if ($tariff) {
            // Update existing tariff
            DB::table('regions_account_type_cost')->where('id', $tariff->id)->update([
                'billing_type' => $billingType,
                'is_water' => true,
                'is_electricity' => true,
                'water_in' => json_encode($rates['water_in']),
                'electricity' => json_encode($rates['electricity']),
                'vat_percentage' => $rates['vat'],
                'updated_at' => now(),
            ]);
            return $tariff->id;
        }
        return DB::table('regions_account_type_cost')->insertGetId([
            'region_id' => $regionId,
            'template_name' => $name,
            'billing_type' => $billingType,
            'is_water' => true,
            'is_electricity' => true,
            'vat_percentage' => $rates['vat'],
            'billing_day' => 10,
            'read_day' => 5,
            'is_active' => true,
            'effective_from' => Carbon::now()->startOfYear(),
            'water_in' => json_encode($rates['water_in']),
            'water_out' => json_encode([]),
            'electricity' => json_encode($rates['electricity']),
            'fixed_costs' => json_encode([]),
            'customer_costs' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSite(int $userId, int $regionId, string $address, string $billingType): int
    {
        return DB::table('sites')->insertGetId([
            'user_id' => $userId,
            'region_id' => $regionId,
            'title' => $address,
            'address' => $address,
            'lat' => '-29.85' . rand(10, 99),
            'lng' => '31.0' . rand(10, 99),
            'email' => '',
            'billing_type' => $billingType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAccount(int $siteId, int $tariffId, string $name, string $number): int
    {
        return DB::table('accounts')->insertGetId([
            'site_id' => $siteId,
            'tariff_template_id' => $tariffId,
            'account_name' => $name,
            'account_number' => $number,
            'bill_day' => null,
            'read_day' => null,
            'bill_read_day_active' => false,
            'water_email' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createMeter(int $accountId, int $meterTypeId, string $title, string $number): int
    {
        return DB::table('meters')->insertGetId([
            'account_id' => $accountId,
            'meter_category_id' => null,
            'meter_type_id' => $meterTypeId,
            'meter_title' => $title,
            'meter_number' => $number,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addReadings(int $meterId, array $readings): void
    {
        foreach ($readings as $reading) {
            DB::table('meter_readings')->insert([
                'meter_id' => $meterId,
                'reading_value' => $reading['value'],
                'reading_date' => Carbon::parse($reading['date']),
                'reading_type' => 'ACTUAL',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addPayments(int $accountId, array $payments): void
    {
        foreach ($payments as $payment) {
            DB::table('payments')->insert([
                'account_id' => $accountId,
                'amount' => $payment['amount'],
                'payment_date' => Carbon::parse($payment['date']),
                'payment_method' => $payment['method'],
                'reference' => $payment['ref'],
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}






