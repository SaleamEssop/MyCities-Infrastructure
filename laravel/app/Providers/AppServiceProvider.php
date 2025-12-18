<?php

namespace App\Providers;

use App\Models\MeterReadings;
use App\Observers\MeterReadingObserver;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        MeterReadings::observe(MeterReadingObserver::class);
        //
    }
}
