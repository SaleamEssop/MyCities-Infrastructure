<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class TestSiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Uses upsert pattern - safe to run multiple times.
     *
     * @return void
     */
    public function run()
    {
        // Find an existing user (demo or admin)
        $user = User::where('email', 'demo@mycities.co.za')->first()
            ?? User::where('email', 'admin@mycities.co.za')->first()
            ?? User::first();

        if (!$user) {
            $this->command->warn('No user found for test site, skipping...');
            return;
        }

        // Check if test site already exists (by title and user)
        $existingSite = DB::table('sites')
            ->where('user_id', $user->id)
            ->where('title', 'Test Site')
            ->first();

        if ($existingSite) {
            // Update existing site
            DB::table('sites')
                ->where('id', $existingSite->id)
                ->update([
                    'lat' => '-26.2041',
                    'lng' => '28.0473',
                    'address' => '123 Test Street, Johannesburg, South Africa',
                    'updated_at' => now(),
                ]);
            $this->command->info('Test site updated');
        } else {
            // Insert new site
            DB::table('sites')->insert([
                'user_id' => $user->id,
                'title' => 'Test Site',
                'lat' => '-26.2041',
                'lng' => '28.0473',
                'address' => '123 Test Street, Johannesburg, South Africa',
                'email' => 'testsite@example.com',
                'billing_type' => 'date_to_date',
                'site_username' => 'test_site_user',
                'site_password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('Test site created');
        }
    }
}