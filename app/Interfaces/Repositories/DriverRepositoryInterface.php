<?php

namespace App\Interfaces\Repositories;
use App\Models\Driver;
use Illuminate\Support\Collection;

interface DriverRepositoryInterface
{
    public function findNearbyDrivers(float $latitude, float $longitude, float $radius): Collection;
}
