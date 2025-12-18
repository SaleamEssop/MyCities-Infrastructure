<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'site_name' => $this->faker->company(),
            'site_address' => $this->faker->address(),
        ];
    }
}
