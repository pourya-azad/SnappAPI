<?php

namespace App\Interfaces\Services;

use Illuminate\Support\Collection;

interface TripServiceInterface
{
    public function findNearbyDrivers(float $pickupLatitude, float $pickupLongitude, float $radius = 5): Collection;
    public function calculateTripCost(float $pickupLatitude, float $pickupLongitude, float $destLatitude, float $destLongitude): array;
}
