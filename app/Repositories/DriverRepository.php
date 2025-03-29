<?php

namespace App\Repositories;

use App\Interfaces\Repositories\DriverRepositoryInterface;
use App\Models\Driver;
use Illuminate\Support\Collection;

class DriverRepository implements DriverRepositoryInterface
{
    public function findNearbyDrivers(float $latitude, float $longitude, float $radius): Collection
    {
        return Driver::selectRaw("*, 
        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
        [$latitude, $longitude, $latitude])
        ->where('is_active', true)
        ->whereRaw("distance <= ?", [$radius]) // استفاده از whereRaw به جای having
        ->orderBy('distance')
        ->get();

    }
}
