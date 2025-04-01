<?php

namespace Tests\Feature;

use App\Http\Controllers\RideController;
use App\Http\Requests\CompleteRideRequest;
use App\Services\RideCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CompleteRideControllerTest extends TestCase
{
    protected $rideCompletionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rideCompletionService = $this->mock(RideCompletionService::class);

        $this->app->instance(RideCompletionService::class, $this->rideCompletionService);
    }

    public function test_complete_ride_successfully()
    {
        $fakeTotalTime = 3;

        $request = new CompleteRideRequest([
            "driver_id" => 1,
            "trip_id" => 2,
        ]);

        $this->rideCompletionService
            ->shouldReceive('completeRide')
            ->with(2) // مقدار trip_id که به متد می‌فرستیم
            ->once()
            ->andReturn($fakeTotalTime);

        $controller = app(RideController::class);
        $response = $controller->complete($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'message' => 'Ride completed successfully',
                'data' => [
                    'total_time' => $fakeTotalTime
                ],
            ]),
            $response->getContent()
        );
    }

    public function test_complete_ride_fails_with_exception()
    {
        $request = new CompleteRideRequest([
            "driver_id" => 1,
            "trip_id" => 2,
        ]);

        $this->rideCompletionService
            ->shouldReceive('completeRide')
            ->with(2)
            ->once()
            ->andThrow(new \Exception('Something went wrong'));

        \Log::shouldReceive('error')
            ->once()
            ->with('An error occurred while completing the ride: ', Mockery::on(function ($context) {
                return isset($context['error']) && $context['error'] === 'Something went wrong'
                    && $context['trip_id'] === 2;
            }));

        $controller = app(RideController::class);
        $response = $controller->complete($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'message' => 'An error occurred while completing the ride',
                'error' => config('app.debug') ? 'Something went wrong' : null,
            ]),
            $response->getContent()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}