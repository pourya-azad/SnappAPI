<?php

use App\Http\Controllers\DriverController;
use App\Http\Controllers\RideRequestController;
use App\Http\Controllers\RideController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource('drivers',DriverController::class);
Route::post('drivers/location', [DriverController::class,'updateLocation']);

Route::post('riderequest/store', [RideRequestController::class, 'store']);

Route::post('login', [AuthController::class,'login']);

Route::middleware('auth:sanctum')->post('logout', [AuthController::class,'logout']);

Route::post('riderequest/accept', [RideRequestController::class,'accept']);

Route::post('ride/complete', [RideController::class,'complete']);