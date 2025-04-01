<?php

namespace Tests\Feature;

use App\Models\CurrentRide;
use App\Models\RideRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;
use App\Http\Controllers\UserController;

class StatusUserContorllerTest extends TestCase
{

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * تست وضعیت وقتی درخواست در حالت pending است
     */
    public function test_status_when_request_is_pending(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'user');

        RideRequest::factory()->create([
            'user_id' => $user->id,
            'isPending' => true,
        ]);

        $request = Request::create('/api/user/status', 'GET');
        $request->setUserResolver(fn() => $user);

        $controller = app(UserController::class);
        $response = $controller->status($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Your request is pending, please wait!']),
            $response->getContent()
        );
    }

    /**
     * تست وضعیت وقتی راننده درخواست را پذیرفته و در راه است
     */
    public function test_status_when_driver_is_on_the_way(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'user');

        CurrentRide::factory()->create([
            'user_id' => $user->id,
            'isArrived' => false,
        ]);

        $request = Request::create('/api/user/status', 'GET');
        $request->setUserResolver(fn() => $user);

        $controller = app(UserController::class);
        $response = $controller->status($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Your request has been accepted, and the driver is on the way. Please wait!']),
            $response->getContent()
        );
    }

    /**
     * تست وضعیت وقتی کاربر در حال انجام سفر است
     */
    public function test_status_when_user_is_on_ride(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'user');

        CurrentRide::factory()->create([
            'user_id' => $user->id,
            'isArrived' => true,
        ]);

        $request = Request::create('/api/user/status', 'GET');
        $request->setUserResolver(fn() => $user);

        $controller = app(UserController::class);
        $response = $controller->status($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'You are currently on a ride, please end it!']),
            $response->getContent()
        );
    }

    /**
     * تست وضعیت وقتی کاربر هیچ سفری ندارد (بیکار است)
     */
    public function test_status_when_user_is_idle(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'user');

        $request = Request::create('/api/user/status', 'GET');
        $request->setUserResolver(fn() => $user);

        $controller = app(UserController::class);
        $response = $controller->status($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'You are currently idle.']),
            $response->getContent()
        );
    }
}
