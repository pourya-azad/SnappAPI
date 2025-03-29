<?php

namespace App\Providers;

use App\Interfaces\Services\RideCompletionServiceInterface;
use App\Interfaces\Services\DistanceCalculatorServiceInterface;
use App\Interfaces\Repositories\RideRepositoryInterface;
use App\Repositories\DriverRepository;
use App\Interfaces\Repositories\DriverRepositoryInterface;
use App\Repositories\RideRepository;
use App\Services\HaversineDistanceCalculatorService;
use App\Services\RideCompletionService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->bind(DriverRepositoryInterface::class, DriverRepository::class);
        $this->app->bind(DistanceCalculatorServiceInterface::class, HaversineDistanceCalculatorService::class);
        $this->app->bind(RideCompletionServiceInterface::class, RideCompletionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
