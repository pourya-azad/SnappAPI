<?php

namespace App\Http\Controllers;

use App\Interfaces\Controllers\UserControllerInterface;
use App\Models\CurrentRide;
use App\Models\RideRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller implements UserControllerInterface
{

    /**
     * @OA\Get(
     *     path="/api/users/status",
     *     summary="Get user status",
     *     description="Returns the current status of the authenticated user based on their ride activity.",
     *     tags={"Users"},
     *     security={{"Bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User status retrieved successfully",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Your request is pending, please wait!"),
     *                     @OA\Property(property="status_code", type="integer", example=1, description="Status code indicating the user's current state")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Your request has been accepted, and the driver is on the way. Please wait!"),
     *                     @OA\Property(property="status_code", type="integer", example=2, description="Status code indicating the ride is on the way")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="You are currently on a ride, please end it!"),
     *                     @OA\Property(property="status_code", type="integer", example=3, description="Status code indicating the user is on a ride")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="You are currently idle."),
     *                     @OA\Property(property="status_code", type="integer", example=4, description="Status code indicating the user is idle")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function status(Request $request): JsonResponse
    {
        if (RideRequest::where('user_id', $request->user('user')->id)->where('isPending' , true)->exists()) {
            return response()->json([
                'message' => "Your request is pending, please wait!",
            ], 200);
        }
        if (CurrentRide::where('user_id', $request->user('user')->id)->where('isArrived', false)->exists()) {
            return response()->json([
                'message' => "Your request has been accepted, and the driver is on the way. Please wait!",
            ], 200);
        }
        if (CurrentRide::where('user_id', $request->user('user')->id)->where('isArrived', true)->exists()) {
            return response()->json([
                'message' => "You are currently on a ride, please end it!",
            ], 200);
        }
        return response()->json([
            'message' => "You are currently idle."
        ]);

    }
}
