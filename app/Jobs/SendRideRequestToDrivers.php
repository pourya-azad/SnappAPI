<?php

namespace App\Jobs;

use App\Events\RideRequestBroadcast;
use App\Models\RequestDriver;
use App\Models\RideRequest;
use App\Services\RidePreparationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRideRequestToDrivers implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected int $rideRequestId;
    protected float $radius;
    protected int $nextRadiusIndex;

    /**
     * Create a new job instance.
     */
    public function __construct(int $rideRequestId, float $radius, int $nextRadiusIndex)
    {
        $this->rideRequestId = $rideRequestId;
        $this->radius = $radius;
        $this->nextRadiusIndex = $nextRadiusIndex;
    }

    /**
     * Execute the job.
     */
    public function handle(RidePreparationService $ridePreparationService): void
    {
        $rideRequest = RideRequest::find($this->rideRequestId);
        if ($rideRequest->isPending == 0) {
            return;
        }

        $nearbyDrivers = $ridePreparationService->findNearbyDrivers(
            $rideRequest->pickup_latitude,
            $rideRequest->pickup_longitude,
            $this->radius
        );

        $driverIds = collect([]);
        foreach ($nearbyDrivers as $nearbyDriver)
        {
            $exists = RequestDriver::where('request_id', $rideRequest->id)
                ->where('driver_id', $nearbyDriver->id)
                ->exists();

            if (!$exists) {
                $driverIds->push($nearbyDriver->id);
                RequestDriver::create([
                    'request_id' => $rideRequest->id,
                    'driver_id' => $nearbyDriver->id,
                ]);
            }
        }

        if ($driverIds->isNotEmpty())
        {
            event(new RideRequestBroadcast(
                $rideRequest->id,
                $rideRequest->pickup_latitude,
                $rideRequest->pickup_longitude,
                $driverIds->all(),
                $rideRequest->cost
            ));
        }

        $radiuses = [50, 100, 200, 500, 999999999];
        if($this->nextRadiusIndex + 1 < count($radiuses))
        {
            SendRideRequestToDrivers::dispatch(
                $this->rideRequestId,
                $radiuses[$this->nextRadiusIndex + 1],
                $this->nextRadiusIndex + 1
            )->delay(now()->addSeconds(15));
        }
    }
}
