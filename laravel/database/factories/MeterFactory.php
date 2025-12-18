<?php

namespace Database\Factories;

use App\Models\Meter;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeterFactory extends Factory
{
    protected $model = Meter::class;

    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'meter_type_id' => 1,
            'meter_title' => $this->faker->word() . ' Meter',
            'meter_number' => $this->faker->numerify('MTR-######'),
        ];
    }
}
