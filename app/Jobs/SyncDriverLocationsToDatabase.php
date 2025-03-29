<?php

namespace App\Jobs;

use App\Models\Driver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;

class SyncDriverLocationsToDatabase implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $keys = Redis::keys('driver:location:*');

        foreach ($keys as $key) {
            $driverId = str_replace('laravel_database_driver:location:','', $key);
            
            $key = str_replace('laravel_database_','', $key);
            $locationData = json_decode(Redis::get($key), true);

            if ($locationData){
                Driver::findOrFail($driverId)
                ->update([
                    'latitude' => $locationData['latitude'] ?? null,
                    'longitude' => $locationData['longitude'] ?? null,
                ]);
            }
        }
    }
}
