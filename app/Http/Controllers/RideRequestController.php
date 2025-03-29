<?php

namespace App\Http\Controllers;

use App\Events\RideRequestConfirmed;
use App\Events\RideRequestCreated;
use App\Http\Requests\AcceptRideRequestRequest;
use App\Http\Requests\NewRideRequestRequest;
use App\Interfaces\Controllers\RideRequestInterface;
use App\Models\CurrentRide;
use App\Models\RideRequest;
use Illuminate\Http\JsonResponse;


class RideRequestController extends Controller implements RideRequestInterface
{

    public function store(NewRideRequestRequest $request): JsonResponse
    {
        try {
            $rideRequest = RideRequest::create($request->validated());      
        
            \Log::info('Ride request created successfully', [
                'request_id' => $rideRequest->id,
                'user_id' => $request->user()->id ?? null,
                'data' => $request->validated(),
            ]);

            event(new RideRequestCreated($rideRequest));

            return response()->json([
                'message' => 'Ride request created successfully',
                'request_id' => $rideRequest->id,
                'data' => $rideRequest->only(['id', 'user_id']),
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

    public function accept(AcceptRideRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        RideRequest::where(['id'=> $validated['request_id']])->update(['isPending' => False]);

        CurrentRide::create($validated);

        event(new RideRequestConfirmed($validated['driver_id'], RideRequest::where('id', $validated['request_id'])->select('user_id')->first()->user_id));

        return response()->json([
            'message'=> 'update successful!'
        ]);
    }

}
