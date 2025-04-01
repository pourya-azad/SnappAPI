<?php

namespace Tests\Feature;

use App\Events\RideRequestCreated;
use App\Http\Controllers\RideRequestController;
use App\Http\Requests\NewRideRequestRequest;
use App\Models\RideRequest;
use App\Models\User;
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

class StoreRideRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }
    public function it_successfully_creates_ride_request()
    {
        $user = User::factory()->create();
        $requestData = [
            'pickup_latitude' => 35.6892,
            'pickup_longitude' => 51.3890,
            'dest_latitude' => 35.6892,
            'dest_longitude' => 51.3890,
        ];

        $mockRequest = $this->mock(NewRideRequestRequest::class, function ($mock) use ($requestData, $user) {
            $mock->shouldReceive('validated')->andReturn($requestData);
            $mock->shouldReceive('user')->andReturn($user);
            $mock->shouldReceive('all')->andReturn($requestData);
        });
    
        \Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($user, $requestData) {
                return $message === 'Ride request created successfully' &&
                    isset($context['request_id']) &&
                    $context['user_id'] === $user->id &&
                    $context['data'] === $requestData;
            });
    
        \Log::shouldReceive('error')
            ->never();
    
        $controller = new RideRequestController();
        $response = $controller->store($mockRequest);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Ride request created successfully', $responseData['message']);
        $this->assertArrayHasKey('request_id', $responseData);
        $this->assertEquals(['id', 'user_id'], array_keys($responseData['data']));

        $this->assertDatabaseHas('ride_requests', [
            'user_id' => $user->id, 
            'pickup_latitude' => 35.6892,
            'pickup_longitude' => 51.3890,
            'dest_latitude' => 35.6892,
            'dest_longitude' => 51.3890,
        ]);
    
        Event::assertDispatched(RideRequestCreated::class, function ($event) use ($responseData) {
            return $event->rideRequest->id === $responseData['request_id'];
        });
    }
    
    public function it_handles_creation_failure_and_logs_error()
    {
        $user = User::factory()->create();
        $requestData = [
            'pickup_latitude' => 35.6892,
            'pickup_longitude' => 51.3890,
            'dest_latitude' => 35.6892,
            'dest_longitude' => 51.3890,
        ];

        $mockRequest = $this->mock(NewRideRequestRequest::class, function ($mock) use ($requestData, $user) {
            $mock->shouldReceive('validated')->andReturn($requestData);
            $mock->shouldReceive('user')->andReturn($user);
            $mock->shouldReceive('all')->andReturn($requestData);
        });

        RideRequest::shouldReceive('create')
            ->with($requestData)
            ->andThrow(new \Exception('Database error'));
    
        \Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($requestData) {
                return $message === 'Failed to create ride request' &&
                    $context['error'] === 'Database error' &&
                    is_string($context['trace']) &&
                    $context['data'] === $requestData;
            });

        $controller = new RideRequestController();
        $response = $controller->store($mockRequest);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('An error occurred while creating the ride request', $responseData['message']);

        if (config('app.debug')) {
            $this->assertEquals('Database error', $responseData['error']);
        } else {
            $this->assertNull($responseData['error']);
        }

        $this->assertDatabaseMissing('ride_requests', [
            'pickup_latitude' => 35.6892,
            'pickup_longitude' => 51.3890,
            'dest_latitude' => 35.6892,
            'dest_longitude' => 51.3890,
        ]);
    
        Event::assertNotDispatched(RideRequestCreated::class);
    }
    

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

