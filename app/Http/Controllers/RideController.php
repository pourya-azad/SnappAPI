<?php

namespace App\Http\Controllers;


use App\Http\Requests\CompleteRideRequest;
use App\Interfaces\Controllers\TripInterface;
use App\Services\RideCompletionService;

use Illuminate\Http\JsonResponse;

class RideController extends Controller implements TripInterface
{

    private RideCompletionService $rideCompletionService;

    public function __construct(RideCompletionService $rideCompletionService)
    {
        $this->rideCompletionService = $rideCompletionService;
    }

    /**
     * Completes a ride and logs the total time.
     */
    public function complete(CompleteRideRequest $request): JsonResponse
    {
        try {
            $total_time = $this->rideCompletionService->completeRide($request->trip_id);
            
            return response()->json([
                'message' => 'Ride completed successfully',
                'data' => [
                    'total_time' => $total_time
                ],
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to complete ride', [
                'error' => $e->getMessage(),
                'trip_id' => $request->trip_id ?? null,
            ]);

            return response()->json([
                'message' => 'Failed to complete ride',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
}
