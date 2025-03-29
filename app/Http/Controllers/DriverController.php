<?php

namespace App\Http\Controllers;


use App\Http\Requests\UpdateLocationDriverRequest;
use App\Interfaces\Controllers\DriverInterface;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;

class DriverController extends Controller implements DriverInterface
{

    public function updateLocation(UpdateLocationDriverRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $driverId = $validatedData['driver_id'];
            $locationData = Arr::except($validatedData, ['driver_id']);

            $redisKey = "driver:location:{$driverId}";
            
            Redis::set($redisKey, json_encode($locationData));
            Redis::expire($redisKey, 3600);

            \Log::info('Driver location updated successfully: ', [$driverId]);

            return response()->json([
                'message' => 'Driver location updated successfully in Redis',
                'data' => [
                    'driver_id' => $driverId,
                    'location' => $locationData,
                    'updated_at' => now()->toIso8601String(),
                ],
            ], 200);
        }
        catch (\Exception $e) {
            \Log::error('Failed to update driver location: ', [$e->getMessage()]);
            return response()->json([
                'message' => 'Failed to update driver location',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
}
