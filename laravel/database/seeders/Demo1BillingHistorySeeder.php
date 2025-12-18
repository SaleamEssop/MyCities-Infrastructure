<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Meter;
use Carbon\Carbon;

class Demo1BillingHistorySeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Setting up billing history for demo1...');

        // Find demo1's water meter
        $meter = Meter::where('meter_number', 'D2D-WM-001')->first();
        
        if (!$meter) {
            $this->command->error('Meter D2D-WM-001 not found. Run Demo1UserSeeder first.');
            return;
        }

        $accountId = $meter->account_id;

        // Clear existing readings
        DB::table('meter_readings')->where('meter_id', $meter->id)->delete();
        
        // Insert readings for multiple periods
        // Period 1: March 10 -> May 15 = 2450L (66 days)
        // Period 2: May 15 -> June 15 = 1600L (31 days)
        // Period 3: June 15 -> July 20 = 2000L (35 days)
        DB::table('meter_readings')->insert([
            [
                'meter_id' => $meter->id,
                'reading_value' => '5000',
                'reading_date' => '2025-03-10',
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'meter_id' => $meter->id,
                'reading_value' => '7450',
                'reading_date' => '2025-05-15',
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'meter_id' => $meter->id,
                'reading_value' => '9050',
                'reading_date' => '2025-06-15',
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'meter_id' => $meter->id,
                'reading_value' => '11050',
                'reading_date' => '2025-07-20',
                'reading_type' => 'actual',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('  Created 4 meter readings (3 billing periods)');

        // Clear existing payments
        DB::table('payments')->where('account_id', $accountId)->delete();

        // Insert payments
        // Period 1 (March-May): R2450 charged, R2450 paid = R0 balance
        // Period 2 (May-June): R1600 charged, R1000 paid = R600 balance
        // Period 3 (June-July): R2000 charged + R600 B/F = R2600 total, R1000 paid = R1600 owing
        DB::table('payments')->insert([
            [
                'account_id' => $accountId,
                'amount' => 2450.00,
                'payment_date' => '2025-05-12',
                'payment_method' => 'EFT',
                'reference' => 'PAY-2025-001',
                'notes' => 'Full payment for March-May period',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'account_id' => $accountId,
                'amount' => 1000.00,
                'payment_date' => '2025-06-16',
                'payment_method' => 'EFT',
                'reference' => 'PAY-2025-002',
                'notes' => 'Partial payment for May-June period',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->command->info('  Created 2 payments');
        $this->command->info('');
        $this->command->info('Billing History Summary:');
        $this->command->info('  Period 1 (10th March > 15th May): 2450L consumption');
        $this->command->info('    - Payment: R2450.00 on 12 May 2025');
        $this->command->info('    - Balance: R0.00');
        $this->command->info('');
        $this->command->info('  Period 2 (15th May > 15th June): 1600L consumption');
        $this->command->info('    - Payment: R1000.00 on 16 June 2025');
        $this->command->info('    - Balance: R600.00');
        $this->command->info('');
        $this->command->info('  Period 3 (15th June > 20th July): 2000L consumption');
        $this->command->info('    - Balance B/F: R600.00');
        $this->command->info('    - Total Due: ~R2600.00 (depending on tariff)');
        $this->command->info('');
        $this->command->info('Done!');
    }
}






