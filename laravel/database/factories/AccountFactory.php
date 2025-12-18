<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Site;
use App\Models\RegionsAccountTypeCost;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition()
    {
        return [
            'site_id' => Site::factory(),
            'tariff_template_id' => RegionsAccountTypeCost::factory(),
            'account_name' => $this->faker->company(),
            'account_number' => $this->faker->numerify('ACC-######'),
            'billing_date' => now()->addDays(rand(1, 30)),
        ];
    }
}
