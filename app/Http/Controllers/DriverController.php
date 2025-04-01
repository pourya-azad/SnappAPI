<?php

namespace App\Http\Controllers;


use App\Http\Requests\UpdateLocationDriverRequest;
use App\Http\Resources\DriverLocationResource;
use App\Interfaces\Controllers\DriverInterface;
use App\Models\CurrentRide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;

class DriverController extends Controller implements DriverInterface
{


    /**
     * @OA\Post(
     *     path="/api/drivers/location",
     *     summary="Update driver's location",
     *     description="Stores the driver's location in Redis and returns the updated data. Driver ID is retrieved from authenticated user.",
     *     tags={"Drivers"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example=35.6892, description="Latitude of the driver's location"),
     *             @OA\Property(property="longitude", type="number", format="float", example=51.3890, description="Longitude of the driver's location")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Location updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="driver_id", type="integer", example=123),
     *             @OA\Property(
     *                 property="location",
     *                 type="object",
     *                 @OA\Property(property="latitude", type="number", format="float", example=35.6892),
     *                 @OA\Property(property="longitude", type="number", format="float", example=51.3890)
     *             ),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-30T12:34:56Z"),
     *             @OA\Property(property="message", type="string", example="Driver location updated successfully in Redis")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Driver not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized. Please log in.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while updating driver location"),
     *             @OA\Property(property="error", type="string", example="Detailed error message", nullable=true)
     *         )
     *     )
     * )
     */
    public function updateLocation(UpdateLocationDriverRequest $request): JsonResponse
    {
        try {
            $validatedData = collect($request->validated())
            ->put('driver_id', $request->user('driver')->id)
            ->all();

            $locationData = $request->validated();

            $redisKey = "driver:location:{$validatedData['driver_id']}";
            
            Redis::setex($redisKey, 3600, json_encode($validatedData));

            \Log::info('Driver location updated successfully: ', [$validatedData['driver_id']]);

            $responseData = [
                'driver_id' => $validatedData['driver_id'],
                'location' => $locationData,
                'updated_at' => now()->toIso8601String(),
            ];
    
            return (new DriverLocationResource($responseData))
                ->additional(['message' => 'Driver location updated successfully in Redis'])
                ->response()
                ->setStatusCode(200);
        }
        catch (\Exception $e) {
            \Log::error("Failed to update driver location: ", [$e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while updating driver location',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
        
    }

    
    /**
     * @OA\Get(
     *     path="/api/drivers/status",
     *     summary="Get driver status",
     *     description="Returns the current status of the driver based on their ride activity.",
     *     tags={"Drivers"},
     *     security={{"Bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Driver status retrieved successfully",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="You have accepted a request and are heading to the user!")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="You are currently on a ride, please end it!")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="You are currently idle.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Driver not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized. Please log in.")
     *         )
     *     )
     * )
     */
    public function status(Request $request): JsonResponse
    {
        if (CurrentRide::where('driver_id', $request->user('driver')->id)->where('isArrived', false)->exists()) {
            return response()->json([
                'message' => 'You have accepted a request and are heading to the user!',
            ], 200);
        }
        if (CurrentRide::where('driver_id', $request->user('driver')->id)->where('isArrived', true)->exists()) {
            return response()->json([
                'message' => 'You are currently on a ride, please end it!',
            ], 200);
        }
        return response()->json([
            'message' => 'You are currently idle.'
        ], 200);
    }
}
