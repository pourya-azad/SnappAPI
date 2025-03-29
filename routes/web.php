<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-token/{id}', function ($id) {
    $driver = \App\Models\Driver::find($id); // فرض می‌کنیم کاربر با id=2
    $token = $driver->createToken('auth-token')->plainTextToken;
    return response()->json(['token' => $token]);
});





