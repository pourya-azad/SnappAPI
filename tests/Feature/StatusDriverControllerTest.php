<?php

namespace Tests\Feature;

use App\Models\CurrentRide;
use App\Models\Driver;
use App\Models\User;
use App\Models\RideRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;
use App\Http\Controllers\DriverController;

class StatusDriverControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * تست وضعیت وقتی راننده در حال رفتن به سمت کاربر است
     */
    public function test_status_when_driver_is_heading_to_user(): void
    {
        // ایجاد راننده و احراز هویتش
        $driver = Driver::factory()->create();
        $this->actingAs($driver, 'driver');

        // ایجاد یک کاربر و یک درخواست مرتبط
        $user = User::factory()->create();
        $rideRequest = RideRequest::factory()->create(); // ساخت درخواست

        // ایجاد یک سفر با isArrived = false و request_id معتبر
        CurrentRide::factory()->create([
            'driver_id' => $driver->id,
            'user_id' => $user->id,
            'request_id' => $rideRequest->id, // استفاده از request_id معتبر
            'isArrived' => false,
        ]);

        // ایجاد درخواست و فراخوانی متد
        $request = Request::create('/api/driver/status', 'GET');
        $request->setUserResolver(function () use ($driver) {
            return $driver;
        });

        $controller = app(DriverController::class);
        $response = $controller->status($request);

        // بررسی پاسخ
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'message' => 'You have accepted a request and are heading to the user!',
            ]),
            $response->getContent()
        );
    }

    /**
     * تست وضعیت وقتی راننده در حال انجام سفر است
     */
    public function test_status_when_driver_is_on_ride(): void
    {
        // ایجاد راننده و احراز هویتش
        $driver = Driver::factory()->create();
        $this->actingAs($driver, 'driver');

        // ایجاد یک کاربر و یک درخواست مرتبط
        $user = User::factory()->create();
        $rideRequest = RideRequest::factory()->create(); // ساخت درخواست

        // ایجاد یک سفر با isArrived = true و request_id معتبر
        CurrentRide::factory()->create([
            'driver_id' => $driver->id,
            'user_id' => $user->id,
            'request_id' => $rideRequest->id, // استفاده از request_id معتبر
            'isArrived' => true,
        ]);

        // ایجاد درخواست و فراخوانی متد
        $request = Request::create('/api/driver/status', 'GET');
        $request->setUserResolver(function () use ($driver) {
            return $driver;
        });

        $controller = app(DriverController::class);
        $response = $controller->status($request);

        // بررسی پاسخ
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'message' => 'You are currently on a ride, please end it!',
            ]),
            $response->getContent()
        );
    }

    /**
     * تست وضعیت وقتی راننده بیکار است
     */
    public function test_status_when_driver_is_idle(): void
    {
        // ایجاد راننده و احراز هویتش
        $driver = Driver::factory()->create();
        $this->actingAs($driver, 'driver');

        // هیچ سفری برای راننده ایجاد نمی‌کنیم تا بیکار باشه

        // ایجاد درخواست و فراخوانی متد
        $request = Request::create('/api/driver/status', 'GET');
        $request->setUserResolver(function () use ($driver) {
            return $driver;
        });

        $controller = app(DriverController::class);
        $response = $controller->status($request);

        // بررسی پاسخ
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'message' => 'You are currently idle.',
            ]),
            $response->getContent()
        );
    }

    /**
     * تست وضعیت وقتی راننده احراز هویت نشده است
     */
    public function test_status_fails_when_driver_is_not_authenticated(): void
    {
        // درخواست بدون احراز هویت
        $request = Request::create('/api/driver/status', 'GET');

        $controller = app(DriverController::class);

        // بررسی اینکه متد به درستی کار نکنه
        try {
            $response = $controller->status($request);
            $this->fail('Expected an exception due to unauthenticated driver, but none was thrown.');
        } catch (\ErrorException $e) {
            $this->assertStringContainsString('Attempt to read property "id" on null', $e->getMessage());
        }
    }
}