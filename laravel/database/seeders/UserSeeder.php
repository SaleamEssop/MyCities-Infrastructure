<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database with required users.
     *
     * @return void
     */
    public function run()
    {
        // Always create demo user (hardcoded - safe for demo)
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@mycities.co.za'],
            [
                'name' => 'Demo User',
                'contact_number' => '+27 11 123 4567',
                'password' => Hash::make('demo123'),
                'is_admin' => 0,
                'is_super_admin' => 0,
            ]
        );

        if ($demoUser->wasRecentlyCreated) {
            $this->command->info('Demo user created: demo@mycities.co.za / demo123');
        } else {
            $this->command->info('Demo user already exists');
        }

        // Create fixed admin user for testing
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@mycities.co.za'],
            [
                'name' => 'Admin User',
                'contact_number' => '+27 11 000 0000',
                'password' => Hash::make('admin786'),
                'is_admin' => 1,
                'is_super_admin' => 1,
            ]
        );

        if ($adminUser->wasRecentlyCreated) {
            $this->command->info('Admin user created: admin@mycities.co.za / admin786');
        } else {
            // Always update password to ensure it's correct (for testing)
            $adminUser->password = Hash::make('admin786');
            $adminUser->is_admin = 1;
            $adminUser->is_super_admin = 1;
            $adminUser->save();
            $this->command->info('Admin user updated: admin@mycities.co.za / admin786');
        }
    }
}










