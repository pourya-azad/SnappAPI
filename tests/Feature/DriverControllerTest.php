<?php

namespace Tests\Feature;

use App\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class DriverControllerTest extends TestCase
{

    use RefreshDatabase;

    /**
     * تست موفقیت‌آمیز به‌روزرسانی موقعیت درایور
     */
    public function test_update_location_successfully(): void
    {
        $driver = Driver::factory()->create();

        $locationData = [
            'latitude' => 35.6892,
            'longitude' => 51.3890,
        ];

        Redis::shouldReceive('set')->once()->with("driver:location:{$driver->id}", json_encode($locationData));
        Redis::shouldReceive('expire')->once()->with("driver:location:{$driver->id}", 3600);

        $response = $this->postJson('/api/drivers/location',[
            'driver_id'=> $driver->id,
            ] + $locationData
        );

        $response->assertStatus(200)->assertJsonStructure([
            'message',
            'data' => [
                'driver_id',
                'location',
                'updated_at',
            ]])->assertJsonFragment([
                'message' => 'Driver location updated successfully in Redis',
                'driver_id' => $driver->id,
                'location' => $locationData,
            ]);
    }

    /**
     * تست شکست به دلیل داده‌های نامعتبر
     */
    public function test_update_location_fails_with_invalid_data(): void
    {
        $driver = Driver::factory()->create();

        $invalidData = [
            'driver_id' => $driver->id,
            'latitude' => 'invalid',
            'longitude' => 51.3890,
        ];

        $response = $this->postJson('/api/drivers/location', $invalidData);

        $response->assertStatus(422)->assertJsonValidationErrors(['latitude']);
    }

    /**
     * تست شکست به دلیل درایور ناموجود
     */
    public function test_update_location_fails_with_non_existent_driver(): void
    {
        $nonexistentDriverId = 999;

        $locationData = [
            'latitude' => 35.6892,
            'longitude' => 51.3890,
        ];


        $response = $this->postJson('/api/drivers/location', [
            'driver_id' => $nonexistentDriverId,
        ] + $locationData);

        $response->assertStatus(422)->assertJsonValidationErrors(['driver_id']);
    }

    /**
     * تست شکست به دلیل خطای Redis
     */
    public function test_update_location_fails_with_redis_error(): void
    {
        $driver = Driver::factory()->create();

        $locationData = [
            'latitude' => 35.6892,
            'longitude' => 51.3890,
        ];

        Redis::shouldReceive('set')->once()->andThrow(new \Exception('Redis connection failed'));
        \Log::shouldReceive('error')->once()->with('Failed to update driver location: ', ['Redis connection failed']);

        $response = $this->postJson('/api/drivers/location', [
            'driver_id' => $driver->id,
        ] + $locationData);

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Failed to update driver location',
                'error' => 'Redis connection failed',
            ]);
    }
}
