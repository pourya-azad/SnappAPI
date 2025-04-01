<?php

namespace Tests\Feature;

use App\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class DriverControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mockery::close();
    }

    /**
     * تست موفقیت در به‌روزرسانی موقعیت راننده
     */
    public function test_update_location_successfully(): void
    {
        // ایجاد راننده و احراز هویتش
        $driver = Driver::factory()->create();
        $this->actingAs($driver, 'driver');

        $locationData = [
            'latitude' => 35.6892,
            'longitude' => 51.3890,
        ];

        $validatedData = array_merge($locationData, ['driver_id' => $driver->id]);

        // ماک Redis
        Redis::shouldReceive('setex')
            ->once()
            ->with("driver:location:{$driver->id}", 3600, json_encode($validatedData));

        // ماک لاگ موفقیت
        \Log::shouldReceive('info')
            ->once()
            ->with('Driver location updated successfully: ', [$driver->id]);

        // درخواست
        $response = $this->postJson('/api/drivers/location', $locationData);

        // بررسی پاسخ
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [ // اضافه کردن 'data' چون DriverLocationResource احتمالاً این ساختار رو داره
                    'driver_id',
                    'location' => ['latitude', 'longitude'],
                    'updated_at',
                ],
                'message',
            ])
            ->assertJsonFragment([
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
        $this->actingAs($driver, 'driver');

        $invalidData = [
            'latitude' => 'invalid',
            'longitude' => 51.3890,
        ];

        $response = $this->postJson('/api/drivers/location', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude']);
    }

    /**
     * تست شکست به دلیل عدم احراز هویت راننده
     */
    public function test_update_location_fails_with_unauthenticated_driver(): void
    {
        $locationData = [
            'latitude' => 35.6892,
            'longitude' => 51.3890,
        ];

        $response = $this->postJson('/api/drivers/location', $locationData);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * تست شکست به دلیل خطای Redis
     */
    public function test_update_location_fails_with_redis_error(): void
    {
        $driver = Driver::factory()->create();
        $this->actingAs($driver, 'driver');

        $locationData = [
            'latitude' => 35.6892,
            'longitude' => 51.3890,
        ];

        $validatedData = array_merge($locationData, ['driver_id' => $driver->id]);

        // ماک Redis با خطا
        Redis::shouldReceive('setex')
            ->once()
            ->with("driver:location:{$driver->id}", 3600, json_encode($validatedData))
            ->andThrow(new \RedisException('Redis connection failed'));

        // ماک لاگ خطا با پیام و آرگومان دقیق
        \Log::shouldReceive('error')
            ->once()
            ->with('Failed to update driver location: ', ['Redis connection failed']);

        $response = $this->postJson('/api/drivers/location', $locationData);

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'An error occurred while updating driver location',
                'error' => config('app.debug') ? 'Redis connection failed' : null,
            ]);
    }
}