<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaseDataSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('========================================');
        $this->command->info('  Base Data Seeder');
        $this->command->info('========================================');

        // Step 1: Meter Types (uses 'title' column)
        $this->command->info('[1/4] Creating Meter Types...');
        
        $waterTypeId = DB::table('meter_types')->where('title', 'Water')->value('id');
        if (!$waterTypeId) {
            $waterTypeId = DB::table('meter_types')->insertGetId([
                'title' => 'Water',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: Water (ID: $waterTypeId)");
        } else {
            $this->command->info("   Exists: Water (ID: $waterTypeId)");
        }
        
        $electricityTypeId = DB::table('meter_types')->where('title', 'Electricity')->value('id');
        if (!$electricityTypeId) {
            $electricityTypeId = DB::table('meter_types')->insertGetId([
                'title' => 'Electricity',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: Electricity (ID: $electricityTypeId)");
        } else {
            $this->command->info("   Exists: Electricity (ID: $electricityTypeId)");
        }

        // Step 2: Meter Categories (uses 'name' column)
        $this->command->info('[2/4] Creating Meter Categories...');
        
        $categories = ['Water in', 'Water out', 'Electricity'];
        foreach ($categories as $catName) {
            $exists = DB::table('meter_categories')->where('name', $catName)->exists();
            if (!$exists) {
                DB::table('meter_categories')->insert([
                    'name' => $catName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("   Created: $catName");
            } else {
                $this->command->info("   Exists: $catName");
            }
        }

        // Step 3: Region (Durban)
        $this->command->info('[3/4] Creating Region...');
        
        $regionId = DB::table('regions')->where('name', 'like', '%Durban%')->value('id');
        if (!$regionId) {
            $regionId = DB::table('regions')->insertGetId([
                'name' => 'Durban (eThekwini)',
                'water_email' => 'eservices@durban.gov.za',
                'electricity_email' => 'electricity@durban.gov.za',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: Durban (eThekwini) (ID: $regionId)");
        } else {
            $this->command->info("   Exists: Durban (eThekwini) (ID: $regionId)");
        }

        // Step 4: Tariff Template (table: regions_account_type_cost - singular!)
        $this->command->info('[4/4] Creating Tariff Template...');
        
        $tariffId = DB::table('regions_account_type_cost')
            ->where('region_id', $regionId)
            ->where('template_name', 'like', '%Durban%')
            ->value('id');
        
        if (!$tariffId) {
            $tariffId = DB::table('regions_account_type_cost')->insertGetId([
                'region_id' => $regionId,
                'template_name' => 'Durban Residential Water Tariff',
                'is_water' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   Created: Tariff Template (ID: $tariffId)");
        } else {
            $this->command->info("   Exists: Tariff Template (ID: $tariffId)");
        }

        $this->command->info('');
        $this->command->info('Base Data Complete!');
    }
}