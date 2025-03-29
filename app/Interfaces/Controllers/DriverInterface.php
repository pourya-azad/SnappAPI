<?php

namespace App\Interfaces\Controllers;
use App\Http\Requests\UpdateLocationDriverRequest;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

interface DriverInterface
{

    public function updateLocation(UpdateLocationDriverRequest $request): JsonResponse;
}
