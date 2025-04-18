<?php

namespace App\Http\Controllers;

use App\Interfaces\Services\PaymentGatewayInterface;
use App\Models\Invoice;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentGatewayInterface $paymentService
    ) {
    }

    /**
     * Pay an invoice for a ride.
     *
     * @OA\Post(
     *     path="/api/pay",
     *     summary="Pay an Invoice",
     *     description="Pay an invoice for a Ride by Ride Request Id",
     *     tags={"Payment"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ride_request_id"},
     *             @OA\Property(property="ride_request_id", type="integer", example=1, description="The ID of the Ride Request to Pay")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ride Payment Url generated Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_url", type="string", example="https://www..."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request due to invalid data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="error returned by Payment Gateway")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized. Please log in.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="ride request not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No unpaid ride request!"),
     *         )
     *     )
     * )
     */
    public function pay(Request $request)
    {
        $validated = $request->validate([
            'ride_request_id' => ['required', 'exists:ride_requests,id'],
        ]);
        $user      = $request->user('user');
        $invoice   = Invoice::where('ride_request_id', $validated['ride_request_id'])
            ->where('user_id', $user->id)
            ->where('isPaid', false)
            ->first();

        if ( ! $invoice) {
            return response()->json([
                "message" => "No unpaid ride request!",
            ], 404);
        }

        $paymentResponse = $this->paymentService->request($invoice, ['email' => $user->email]);
        if ($paymentResponse['status']) {
            return response()->json([
                "payment_url" => $paymentResponse['payment_url'],
            ]);
        }

        return response()->json([
            "message" => $paymentResponse['message'],
        ], 400);
    }

    /**
     * Verify a payment for a ride request.
     *
     * @OA\Get(
     *     path="/api/payment-verify",
     *     summary="Verify Payment",
     *     description="Verify a payment for a ride request using Authority and Status returned from the payment gateway.",
     *     tags={"Payment"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="Authority",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Authority code returned by the payment gateway",
     *         example="S00000000000000000000000000000026mxp"
     *     ),
     *     @OA\Parameter(
     *         name="Status",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Payment status returned by the payment gateway",
     *         example="OK"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment verification successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transaction Completed"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Payment verification failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transaction not verified")
     *         )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="User not authenticated",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthorized. Please log in.")
     *          )
     *      ),
     * )
     */
    public function verify(Request $request, Invoice $invoice)
    {
        $verifyData = [
            'status'    => $request->input('Status', 'NOK'),
            'authority' => $request->input('Authority'),
        ];

        $isVerified = $this->paymentService->verify($invoice, $verifyData);
        if ($isVerified) {
            return response()->json([
                "message" => "Transaction Completed",
            ]);
        }

        return response()->json([
            "message" => "Transaction not verified",
        ], 400);
    }
}
