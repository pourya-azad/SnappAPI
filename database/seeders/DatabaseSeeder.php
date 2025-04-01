<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\RideRequest;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();
        Driver::factory(10)->create();
        RideRequest::factory(10)->create();
        
        User::factory(['email' => 'user@example.com', 'password'=> Hash::make('password123')])->create();
        Driver::factory(["email" => "driver@example.com" ,'password'=> Hash::make('password123')])->create();


    }
}
