<?php

namespace App\Http\Controllers\Auth\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/users/login",
     *     summary="User login and token retrieval",
     *     tags={"User Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="User's email"),
     *             @OA\Property(property="password", type="string", example="password123", description="User's password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login and token retrieval",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="1|randomstring123456789", description="User authentication token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid email or password",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid username or password.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();
        \Log::debug($user->password);
        if ($user && Hash::check($credentials['password'], $user->password)) {
            $token = $user->createToken('user-token')->plainTextToken;
            return response()->json([
                'token' => $token,
            ]);
        }

        return response()->json(['message' => 'Invalid username or password.'], 401);
    }

    /**
     * @OA\Post(
     *     path="/api/users/logout",
     *     summary="User logout",
     *     tags={"User Authentication"},
     *     security={{"Bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful logout",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token not provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Valid token was not provided.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not logged in",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please log in.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        if (!$request->bearerToken()) {
            return response()->json(['message' => 'Valid token was not provided.'], 400);
        }

        $user = $request->user('user');
        if (!$user) {
            return response()->json(['message' => 'Please log in.'], 401);
        }

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out.']);
    }
}
