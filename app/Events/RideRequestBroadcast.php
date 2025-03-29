<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
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

    /**
     * Create a new event instance.
     */
    public function __construct($requestId, $pickup_latitude, $pickup_longitude, $driverIds)
    {
        $this->requestId = $requestId;
        $this->pickup_latitude = $pickup_latitude;
        $this->pickup_longitude = $pickup_longitude;
        $this->driverIds = $driverIds;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): Channel
    {
        return new Channel('driver');
    }

    public function broadcastWith()
    {
        return [
            'requestId' => $this->requestId,
            'driverIds' => $this->driverIds,
            'pickup_latitude' => $this->pickup_latitude,
            'pickup_longitude'=> $this->pickup_longitude,
        ];
    }
}
