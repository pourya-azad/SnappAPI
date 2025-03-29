<?php

namespace App\Listeners;

use App\Events\RideRequestBroadcast;
use App\Events\RideRequestCreated;
use App\Services\RidePreparationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessUserRideRequest
{

    public $RidePreparationService;
    /**
     * Create the event listener.
     */
    public function __construct(RidePreparationService $RidePreparationService)
    {
        $this->RidePreparationService = $RidePreparationService;
    }

    /**
     * Handle the event.
     */
    public function handle(RideRequestCreated $event): void
    {
        $driverIds = collect([]);
        $nearbyDrivers = $this->RidePreparationService->findNearbyDrivers($event->rideRequest->pickup_latitude, $event->rideRequest->pickup_longitude, 99999999);
        
        foreach( $nearbyDrivers as $nearbyDriver ) 
        {   
            $driverIds->push($nearbyDriver->id);
        }


        $res = [];
        $res = $this->RidePreparationService->calculateTripCost($event->rideRequest->pickup_latitude, $event->rideRequest->pickup_longitude, $event->rideRequest->dest_latitude, $event->rideRequest->dest_longitude);
        


        event( new RideRequestBroadcast($event->rideRequest->id ,$event->rideRequest->pickup_latitude, $event->rideRequest->pickup_longitude, $driverIds->all()));

    }
}
