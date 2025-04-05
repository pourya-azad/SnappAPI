<?php

use App\Models\Driver;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

//Broadcast::channel('riderequests', function ($user) {
//    return true;
//});

Broadcast::channel('drivers.{id}', function ($driver, $id) {
   return (int) $driver->id === (int) $id;
//    return true;
}, ['guards' => ['driver']]);

