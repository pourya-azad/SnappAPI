<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideRequest extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = "ride_requests";

    public $timestamps = false;

    protected $casts = [
        'pickup_latitude' => 'float',
        'pickup_longitude' => 'float',
        'dest_latitude' => 'float',
        'dest_longitude' => 'float',
    ];
}
