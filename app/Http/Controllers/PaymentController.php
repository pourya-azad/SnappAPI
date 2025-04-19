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
     *     description="Initiates payment for a ride invoice using the provided Ride Request ID.
     *                  The invoice must belong to the authenticated user and must not be already paid.
     *                  If a matching unpaid invoice is found, a payment URL is generated and returned.",
     *     tags={"Payment"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ride_request_id"},
     *             @OA\Property(property="ride_request_id", type="integer", example=1, description="The ID of the Ride Request to pay for")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ride payment URL generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_url", type="string", example="https://www.example.com/pay/xyz")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request due to payment gateway error or invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error returned by Payment Gateway")
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
     *         description="No unpaid ride request found for this user and ride ID",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No unpaid ride request!")
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
     *     path="/api/payment-verify/{invoice}",
     *     summary="Verify Payment",
     *     description="Verifies a payment for a ride request using Authority and Status parameters returned from the payment gateway.
     *                  The invoice is identified by its ID in the path.",
     *     tags={"Payment"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the invoice to verify",
     *         example=42
     *     ),
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
     *             @OA\Property(property="message", type="string", example="Transaction Completed")
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
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized. Please log in.")
     *         )
     *     )
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
