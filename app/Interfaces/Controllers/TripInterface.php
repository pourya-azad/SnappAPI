<?php

namespace App\Interfaces\Controllers;

use App\Http\Requests\CompleteRideRequest;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface TripInterface
{
    public function complete(CompleteRideRequest $request): JsonResponse;
}
