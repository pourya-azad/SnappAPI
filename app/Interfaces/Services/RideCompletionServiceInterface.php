<?php

namespace App\Interfaces\Services;

interface RideCompletionServiceInterface
{
    public function completeRide(int $tripId): array;
}
