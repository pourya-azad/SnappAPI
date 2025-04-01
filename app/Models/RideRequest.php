<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function CurrentRide(): BelongsTo
    {
        return $this->belongsTo(CurrentRide::class,'id','request_id');
    }
}
