<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\GoogleMapsService;
use App\Services\LocationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleMapsService::class, function ($app) {
            return new GoogleMapsService();
        });

        $this->app->singleton(LocationService::class, function ($app) {
            return new LocationService($app->make(GoogleMapsService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
