<?php

namespace App\Listeners;

use App\Events\RideRequestBroadcast;
use App\Events\RideRequestCreated;
use App\Jobs\SendRideRequestToDrivers;
use App\Models\RequestDriver;
use App\Services\RidePreparationService;

class ProcessUserRideRequest
{


    /**
     * Handle the event.
     */
    public function handle(RideRequestCreated $event): void
    {
        SendRideRequestToDrivers::dispatch($event->rideRequest->id, 5, 0);
    }
}
