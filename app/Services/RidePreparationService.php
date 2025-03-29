<?php

namespace App\Services;

use App\Interfaces\Services\DistanceCalculatorServiceInterface;
use App\Interfaces\Repositories\DriverRepositoryInterface;
use App\Interfaces\Services\TripServiceInterface;
use Illuminate\Support\Collection;

class RidePreparationService implements TripServiceInterface
{
    private const COST_PER_KM = 10000;
    private const BASE_COST = 5000;

    private $driverRepository;
    private $distanceCalculatorService;

    public function __construct(DriverRepositoryInterface $driverRepository, DistanceCalculatorServiceInterface $distanceCalculatorService)
    {
        $this->driverRepository = $driverRepository;
        $this->distanceCalculatorService = $distanceCalculatorService;
    }

    public function findNearbyDrivers(float $pickupLatitude, float $pickupLongitude, float $radius = 5): Collection
    {
        return $this->driverRepository->findNearbyDrivers($pickupLatitude, $pickupLongitude, $radius);
    }

    public function calculateTripCost(float $pickupLatitude, float $pickupLongitude, float $destLatitude, float $destLongitude): array
    {
        if (!is_numeric($pickupLatitude) || !is_numeric($pickupLongitude) ||
            !is_numeric($destLatitude) || !is_numeric($destLongitude)) {
            throw new \InvalidArgumentException('Coordinates must be numeric');
        }

        $distance = $this->distanceCalculatorService->calculate($pickupLatitude, $pickupLongitude, $destLatitude, $destLongitude);
        $cost = self::BASE_COST + ($distance * self::COST_PER_KM);

        return [
            'distance_km' => round($distance, 2),
            'cost' => (int) round($cost),
            'currency' => 'IRR',
        ];
    }
}