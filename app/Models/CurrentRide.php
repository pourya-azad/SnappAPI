<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CurrentRide extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function Driver(): HasOne
    {
        return $this->hasOne(Driver::class,"id","driver_id");
    }

    public function Request(): HasOne
    {
        return $this->hasOne(RideRequest::class,"id","request_id");
    }
}
