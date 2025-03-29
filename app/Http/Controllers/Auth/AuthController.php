<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
{
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $driver = Driver::where('email', $credentials['email'])->first();

        if ($driver && Hash::check($credentials['password'], $driver->password)) {
        
            $token = $driver->createToken('driver-token')->plainTextToken;

            return response()->json([
                'token' => $token,
            ]);
        }

        return response()->json(['message' => 'نام کاربری یا رمز عبور اشتباه است.'], 401);
}


    public function logout(Request $request)
{
    // بررسی اینکه توکن در هدر Authorization وجود دارد
    if (!$request->bearerToken()) {
        return response()->json(['message' => 'توکن معتبر ارسال نشده است.'], 400);
    }

    // بررسی اینکه آیا کاربر احراز هویت شده است با استفاده از گارد 'driver'
    $user = $request->user('driver');
    if (!$user) {
        return response()->json(['message' => 'لطفاً وارد سیستم شوید.'], 401);
    }

    // حذف توکن جاری
    $user->currentAccessToken()->delete();

    return response()->json(['message' => 'با موفقیت خارج شدید.']);
}

    
}
