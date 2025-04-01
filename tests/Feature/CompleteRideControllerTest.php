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

        // ایجاد Mock برای RideCompletionService
        $this->rideCompletionService = $this->mock(RideCompletionService::class);

        // جایگذاری Mock در سیستم لاراول
        $this->app->instance(RideCompletionService::class, $this->rideCompletionService);
    }

    public function test_complete_ride_successfully()
    {
        // داده‌ی ساختگی برای پاسخ
        $fakeTotalTime = 3;

        // شبیه‌سازی درخواست
        $request = new CompleteRideRequest([
            "driver_id" => 1,
            "trip_id" => 2,
        ]);

        // تنظیم رفتار Mock برای سرویس
        $this->rideCompletionService
            ->shouldReceive('completeRide')
            ->with(2) // مقدار trip_id که به متد می‌فرستیم
            ->once()
            ->andReturn($fakeTotalTime);

        // ایجاد کنترلر با سرویس mock شده
        $controller = app(RideController::class);
        $response = $controller->complete($request);

        // بررسی پاسخ
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
        // شبیه‌سازی درخواست
        $request = new CompleteRideRequest([
            "driver_id" => 1,
            "trip_id" => 2,
        ]);

        // تنظیم رفتار Mock برای پرتاب استثنا
        $this->rideCompletionService
            ->shouldReceive('completeRide')
            ->with(2)
            ->once()
            ->andThrow(new \Exception('Something went wrong'));

        // تنظیم Log برای بررسی لاگ‌گذاری با پیام و آرگومان‌های دقیق
        \Log::shouldReceive('error')
            ->once()
            ->with('An error occurred while completing the ride: ', Mockery::on(function ($context) {
                return isset($context['error']) && $context['error'] === 'Something went wrong'
                    && $context['trip_id'] === 2;
            }));

        // ایجاد کنترلر با سرویس Mock شده
        $controller = app(RideController::class);
        $response = $controller->complete($request);

        // بررسی پاسخ
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