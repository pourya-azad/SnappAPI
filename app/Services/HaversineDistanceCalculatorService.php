<?php

namespace App\Services;

use App\Interfaces\Services\DistanceCalculatorServiceInterface;

class HaversineDistanceCalculatorService implements DistanceCalculatorServiceInterface
{
    private const EARTH_RADIUS_KM = 6371;

    public function calculate(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return self::EARTH_RADIUS_KM * $c;
    }
}
