<?php

namespace Database\Factories;

use App\Models\RegionsAccountTypeCost;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegionsAccountTypeCostFactory extends Factory
{
    protected $model = RegionsAccountTypeCost::class;

    public function definition()
    {
        return [
            'template_name' => $this->faker->words(3, true),
            'region_id' => null,
            'start_date' => now()->subYear(),
            'end_date' => now()->addYear(),
            'is_water' => 1,
            'is_electricity' => 1,
            'vat_percentage' => 15,
            'is_active' => 1,
            'billing_day' => 20,
            'read_day' => 5,
            'billing_type' => 'MONTHLY',
            'water_in' => [],
            'water_out' => [],
            'electricity' => [],
            'additional' => [],
            'fixed_costs' => [],
            'customer_costs' => [],
        ];
    }
}
