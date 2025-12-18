<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Meter;
use App\Models\MeterReadings;
use App\Models\Site;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WipeLegacyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:wipe-legacy-data {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe all legacy data from the database (users, sites, accounts, meters, readings, billing data) except admin accounts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Show warning
        $this->warn('⚠️  WARNING: This will permanently delete all legacy data!');
        $this->line('');
        $this->line('The following data will be deleted:');
        $this->line('  - All non-admin users');
        $this->line('  - All sites');
        $this->line('  - All accounts');
        $this->line('  - All meters');
        $this->line('  - All meter readings');
        $this->line('  - All billing/payment data');
        $this->line('');

        // Count what will be deleted
        $userCount = User::where('is_admin', '!=', 1)->count();
        $siteCount = Site::count();
        $accountCount = Account::count();
        $meterCount = Meter::count();
        $readingCount = MeterReadings::count();

        $this->info("Data to be deleted:");
        $this->line("  - Users (non-admin): {$userCount}");
        $this->line("  - Sites: {$siteCount}");
        $this->line("  - Accounts: {$accountCount}");
        $this->line("  - Meters: {$meterCount}");
        $this->line("  - Meter Readings: {$readingCount}");
        $this->line('');

        // Confirm unless --force flag is used
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to proceed? This action cannot be undone!')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->line('');
        $this->info('Starting legacy data wipe...');

        DB::beginTransaction();

        try {
            // Delete meter readings first (no cascade dependencies)
            $deletedReadings = MeterReadings::count();
            MeterReadings::query()->delete();
            $this->line("  ✓ Deleted {$deletedReadings} meter readings");
            Log::info("WipeLegacyData: Deleted {$deletedReadings} meter readings");

            // Delete meters
            $deletedMeters = Meter::count();
            Meter::query()->delete();
            $this->line("  ✓ Deleted {$deletedMeters} meters");
            Log::info("WipeLegacyData: Deleted {$deletedMeters} meters");

            // Delete fixed costs first (before accounts) to avoid cascade issues
            if (DB::getSchemaBuilder()->hasTable('fixed_costs')) {
                $deletedFixedCosts = DB::table('fixed_costs')->count();
                DB::table('fixed_costs')->delete();
                $this->line("  ✓ Deleted {$deletedFixedCosts} fixed costs");
                Log::info("WipeLegacyData: Deleted {$deletedFixedCosts} fixed costs");
            }

            if (DB::getSchemaBuilder()->hasTable('account_fixed_costs')) {
                $deletedAccountFixedCosts = DB::table('account_fixed_costs')->count();
                DB::table('account_fixed_costs')->delete();
                $this->line("  ✓ Deleted {$deletedAccountFixedCosts} account fixed costs");
                Log::info("WipeLegacyData: Deleted {$deletedAccountFixedCosts} account fixed costs");
            }

            // Delete accounts using bulk deletion now that related tables are cleared
            $deletedAccounts = Account::count();
            Account::query()->delete();
            $this->line("  ✓ Deleted {$deletedAccounts} accounts");
            Log::info("WipeLegacyData: Deleted {$deletedAccounts} accounts");

            // Delete sites
            $deletedSites = Site::count();
            Site::query()->delete();
            $this->line("  ✓ Deleted {$deletedSites} sites");
            Log::info("WipeLegacyData: Deleted {$deletedSites} sites");

            // Delete non-admin users
            $deletedUsers = User::where('is_admin', '!=', 1)->count();
            User::where('is_admin', '!=', 1)->delete();
            $this->line("  ✓ Deleted {$deletedUsers} non-admin users");
            Log::info("WipeLegacyData: Deleted {$deletedUsers} non-admin users");

            // Delete payments if the table exists
            if (DB::getSchemaBuilder()->hasTable('payments')) {
                $deletedPayments = DB::table('payments')->count();
                DB::table('payments')->delete();
                $this->line("  ✓ Deleted {$deletedPayments} payments");
                Log::info("WipeLegacyData: Deleted {$deletedPayments} payments");
            }

            DB::commit();

            $this->line('');
            $this->info('✅ Legacy data wipe completed successfully!');
            Log::info('WipeLegacyData: Legacy data wipe completed successfully');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('❌ Error during legacy data wipe: ' . $e->getMessage());
            Log::error('WipeLegacyData: Error during legacy data wipe - ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
