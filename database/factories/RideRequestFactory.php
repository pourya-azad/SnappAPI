<?php

namespace Database\Factories;

use App\Models\RideRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RideRequest>
 */
class RideRequestFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pickup_latitude' => rand(34.0000, 35.0000),   
            'pickup_longitude' => rand(50.0000, 51.0000),  
            'dest_latitude' => rand(34.0000, 35.0000), 
            'dest_longitude' => rand(50.0000, 51.0000), 
            'user_id' => User::factory(),
            'cost' => fake()->numberBetween(1,1000),
            'distance_km' => fake()->numberBetween(1,1000),
        ];
    }
}
