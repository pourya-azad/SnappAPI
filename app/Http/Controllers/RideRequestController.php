<?php

namespace App\Http\Controllers;

use App\Events\RideRequestConfirmed;
use App\Events\RideRequestCreated;
use App\Http\Requests\AcceptRideRequestRequest;
use App\Http\Requests\NewRideRequestRequest;
use App\Interfaces\Controllers\RideRequestInterface;
use App\Models\CurrentRide;
use App\Models\Driver;
use App\Models\RideRequest;
use App\Services\RidePreparationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;


class RideRequestController extends Controller implements RideRequestInterface
{

    public $ridePreparationService;

    public function __construct(RidePreparationService $ridePreparationService){
        $this->ridePreparationService = $ridePreparationService;
    }


/**
 * @OA\Post(
 *     path="/api/ride-requests/store",
 *     summary="Create a new ride request",
 *     tags={"Ride Requests"},
 *     security={{"Bearer": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"pickup_latitude", "pickup_longitude", "dest_latitude", "dest_longitude"},
 *             @OA\Property(property="pickup_latitude", type="number", format="float", example=35.6892, description="Latitude of the pickup location"),
 *             @OA\Property(property="pickup_longitude", type="number", format="float", example=51.3890, description="Longitude of the pickup location"),
 *             @OA\Property(property="dest_latitude", type="number", format="float", example=35.7000, description="Latitude of the destination"),
 *             @OA\Property(property="dest_longitude", type="number", format="float", example=51.4000, description="Longitude of the destination")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Ride request created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ride request created successfully"),
 *             @OA\Property(property="request_id", type="integer", example=1),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1, description="Ride request ID"),
 *                 @OA\Property(property="user_id", type="integer", example=1, description="User ID"),
 *                 @OA\Property(property="cost", type="number", format="float", example=15000.50, description="Cost of the ride"),
 *                 @OA\Property(property="distance_km", type="number", format="float", example=5.2, description="Distance in kilometers")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request due to existing pending request or current ride",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="message", type="string", example="You already have a pending request!")
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="message", type="string", example="You are already assigned to another ride")
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="User not authenticated",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthorized. Please log in.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred while creating the ride request"),
 *             @OA\Property(property="error", type="string", example="Detailed error message", nullable=true)
 *         )
 *     )
 * )
 */
    public function store(NewRideRequestRequest $request): JsonResponse
    {
        try {

            $trip = $this->ridePreparationService->calculateTripCost($request['pickup_latitude'], $request['pickup_longitude'], $request['dest_latitude'], $request['dest_longitude']);

            $validatedData = collect($request->validated())
            ->put('user_id', $request->user('user')->id)
            ->put('cost', $trip['cost'])
            ->put('distance_km', $trip['distance_km'])
            ->all();


            if (RideRequest::where('user_id', $validatedData['user_id'])->where('isPending', true)->exists()) {
                return response()->json([
                    'message' => "You already have a pending request!",
                ], 400);
            }

            if (CurrentRide::where('user_id', $validatedData['user_id'])->exists()) {
                return response()->json([
                    'message' => 'you are already assigned to another ride',
                ], 400);
            }

            $rideRequest = RideRequest::create($validatedData);

            \Log::info('Ride request created successfully', [
                'request_id' => $rideRequest->id,
                'user_id' => $request->user('user')->id ?? null,
                'data' => $request->validated(),
            ]);

            event(new RideRequestCreated($rideRequest));

            return response()->json([
                'message' => 'Ride request created successfully',
                'request_id' => $rideRequest->id,
                'data' => $rideRequest->only(['id', 'user_id', 'cost', 'distance_km']),
            ], 201);
        } catch(\Exception $e){

            \Log::error('Failed to create ride request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'message' => 'An error occurred while creating the ride request',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

    }


    /**
     * @OA\Post(
     *     path="/api/ride-requests/accept",
     *     summary="Accept a ride request by a driver",
     *     tags={"Ride Requests"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"request_id"},
     *             @OA\Property(property="request_id", type="integer", example=1, description="The ID of the ride request")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ride request accepted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ride request accepted successfully"),
     *             @OA\Property(property="request_id", type="integer", example=1),
     *             @OA\Property(property="driver_id", type="integer", example=2),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1, description="ID of the acceptance record"),
     *                 @OA\Property(property="driver_id", type="integer", example=2, description="Driver ID"),
     *                 @OA\Property(property="request_id", type="integer", example=1, description="Ride request ID")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request due to already accepted request or driver assignment",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="This ride request has already been accepted")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="This driver is already assigned to another ride")
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while accepting the ride request"),
     *             @OA\Property(property="error", type="string", example="Detailed error message", nullable=true)
     *         )
     *     )
     * )
     */
    public function accept(AcceptRideRequestRequest $request): JsonResponse
    {
        try {
            $validatedData = collect($request->validated())
            ->put('driver_id', $request->user('driver')->id)
            ->put('user_id', RideRequest::findOrFail($request->request_id)->select('user_id')->first()->user_id ?? null)
            ->all();

            // Check if the request is already accepted
            if (CurrentRide::where('request_id', $validatedData['request_id'])->exists()) {
                return response()->json([
                    'message' => 'This ride request has already been accepted',
                ], 400);
            }

            // Check if the driver is already assigned to another ride
            if (CurrentRide::where('driver_id', $validatedData['driver_id'])->exists()) {
                return response()->json([
                    'message' => 'This driver is already assigned to another ride',
                ], 400);
            }

            // Update the ride request status
            RideRequest::findOrFail($validatedData['request_id'])->update(['isPending' => false]);

            // Update driver id status
            Driver::findOrFail($validatedData['driver_id'])->update(['is_active' => false]);

            // Create a new current ride
            $currentRide = CurrentRide::create($validatedData);

            // Trigger event
            event(new RideRequestConfirmed($validatedData['driver_id'], $rideRequest->user_id));

            // Return detailed response
            return response()->json([
                'message' => 'Ride request accepted successfully',
                'request_id' => $rideRequest->id,
                'driver_id' => $validatedData['driver_id'],
                'data' => $currentRide->only(['id', 'driver_id', 'request_id'])
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Failed to accept ride request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'message' => 'An error occurred while accepting the ride request',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
