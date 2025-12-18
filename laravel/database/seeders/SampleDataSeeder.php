<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use App\Models\Meter;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    /**
     * Seed sample data for testing the application.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@mycities.co.za'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('Admin user created: admin@mycities.co.za / password123');

        // Create test user
        $testUser = User::firstOrCreate(
            ['email' => 'test@mycities.co.za'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
                'is_admin' => false,
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('Test user created: test@mycities.co.za / password123');

        // Create demo user
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('demo123'),
                'is_admin' => false,
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('Demo user created: demo@example.com / demo123');

        $this->command->info('');
        $this->command->info('=== Sample Users Created ===');
        $this->command->info('Admin:  admin@mycities.co.za / password123');
        $this->command->info('Test:   test@mycities.co.za / password123');
        $this->command->info('Demo:   demo@example.com / demo123');
    }
}
