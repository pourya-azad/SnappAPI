<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\RideRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CurrentRide>
 */
class CurrentRideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_id' => Driver::factory(),
            'request_id' => RideRequest::factory(),
        ];
    }
}
