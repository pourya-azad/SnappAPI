<?php

namespace App\Interfaces\Services;

interface DistanceCalculatorServiceInterface
{
    public function calculate(float $lat1, float $lon1, float $lat2, float $lon2): float;
}
