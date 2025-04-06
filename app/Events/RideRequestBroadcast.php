<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideRequestBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $requestId;
    public $pickup_latitude;
    public $pickup_longitude;
    public $driverIds;
    public $tripCost;

    /**
     * Create a new event instance.
     */
    public function __construct($requestId, $pickup_latitude, $pickup_longitude, $driverIds, $tripCost)
    {
        $this->requestId = $requestId;
        $this->pickup_latitude = $pickup_latitude;
        $this->pickup_longitude = $pickup_longitude;
        $this->driverIds = $driverIds;
        $this->tripCost = $tripCost;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     *
     */
    public function broadcastOn()
    {
        return collect($this->driverIds)
            ->map(fn($driverId) => new PrivateChannel("drivers.{$driverId}"))
            ->all();
    }

    public function broadcastWith()
    {
        return [
            'requestId' => $this->requestId,
//            'driverIds' => $this->driverIds,
            'pickup_latitude' => $this->pickup_latitude,
            'pickup_longitude'=> $this->pickup_longitude,
            'trip_cost' => $this->tripCost
        ];
    }

    public function broadcastAs(): string
    {
        return 'ride.request';
    }
}
