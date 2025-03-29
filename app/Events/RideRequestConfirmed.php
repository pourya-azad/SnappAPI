<?php

namespace App\Events;

use App\Service\TripService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideRequestConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driverIds;
    public $userId;
    public $service;
    
    /**
     * Create a new event instance.
     */
    public function __construct(int $driverIds,int $userId)
    {
        $this->driverIds = $driverIds;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): Channel
    {
        return new Channel('user');
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id'=> $this->driverIds,
            'user_id'=> $this->userId,
            'status' => 'accepted'
        ];
    }
}
