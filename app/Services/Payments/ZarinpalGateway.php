<?php

namespace App\Services\Payments;

use App\Interfaces\Services\PaymentGatewayInterface;
use App\Models\Invoice;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZarinPal\Sdk\ClientBuilder;
use ZarinPal\Sdk\Endpoint\PaymentGateway\RequestTypes\RequestRequest;
use ZarinPal\Sdk\HttpClient\Exception\ResponseException;
use ZarinPal\Sdk\Options;
use ZarinPal\Sdk\ZarinPal;

class ZarinpalGateway implements PaymentGatewayInterface
{
    protected string $merchantId;
    protected bool $sandbox;

    protected Options $options;
    protected string $zpPaymentEndPoint;
    protected string $zpStartPgEndPoint;

    public function __construct()
    {
        $this->merchantId = config('services.zarinpal.merchant_id');
        $this->sandbox    = config('services.zarinpal.sandbox');

        $isSandboxPreFix         = $this->sandbox ? 'sandbox' : 'payment';
        $this->zpPaymentEndPoint = "https://{$isSandboxPreFix}.zarinpal.com/pg/v4/payment/";
        $this->zpStartPgEndPoint = "https://{$isSandboxPreFix}.zarinpal.com/pg/StartPay/";
    }

    public function request(Invoice $invoice, array $metadata = []): array
    {
        $paymentData = [
            'merchant_id'  => $this->merchantId,
            'amount'       => $invoice->amount,
            'callback_url' => route('payment-verify', $invoice),
            'description'  => 'پرداخت سفر : ' . $invoice->id,
        ];
        if (isset($metadata['phone'])) {
            $paymentData['metadata']['phone'] = $metadata['phone'];
        }
        if (isset($metadata['email'])) {
            $paymentData['metadata']['email'] = $metadata['email'];
        }
        try {
            $response = Http::post($this->zpPaymentEndPoint . 'request.json', $paymentData);
            if (empty($response['errors'])) {
                $paymentUrl = $this->zpStartPgEndPoint . $response['data']["authority"];
                $invoice->update([
                    'authority' => $response['data']["authority"],
                ]);

                Log::info('payment created for invoice :' . $invoice->id, [
                    "data" => $response,
                ]);

                return ['status' => true, 'payment_url' => $paymentUrl];
            } else {
                Log::error('payment failed to create for invoice :' . $invoice->id, [
                    "error" => $response['errors'],
                ]);

                return ['status' => false, 'message' => $response['errors']];
            }
        } catch (\Exception $e) {
            Log::error('exception caught for payment for invoice :' . $invoice->id, [
                "error"     => $e->getMessage(),
                "errorType" => $e,
            ]);

            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function verify(Invoice $invoice, array $verifyData = []): bool
    {
        $status = $verifyData['status'];
        $authority = $verifyData['authority'];
        if ($status === 'NOK') {
            Log::error('payment for invoice :' . $invoice->id, [
                "error" => "Transaction was cancelled or failed.",
            ]);

            return false;
        }
        // check authority is for this invoice
        if ($invoice->authority !== $authority){
            Log::error("Payment verification failed: authority mismatch", [
                'invoice_id' => $invoice->id,
                'expected'   => $invoice->authority,
                'received'   => $authority,
            ]);
            return false;
        }

        $verifyData = [
            'merchant_id' => $this->merchantId,
            'amount'      => $invoice->amount,
            'authority'   => $authority,
        ];
        try {
            $response = Http::post($this->zpPaymentEndPoint . 'verify.json', $verifyData);

            if ($response['data']['code'] === 100) {
                Log::info('payment for invoice :' . $invoice->id, [
                    "Reference ID: " => $response['data']['ref_id'],
                    "Card PAN: "     => $response['data']['card_pan'],
                    "Fee: "          => $response['data']['fee'],
                ]);

                // update invoice
                $invoice->isPaid  = true;
                $invoice->paid_at = now();
                $invoice->save();

                return true;
            } elseif ($response['data']['code'] === 101) {
                return true;
            } else {
                Log::error('payment for invoice :' . $invoice->id, [
                    "error" => "Transaction failed with code: " . $response['data']['code'],
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('payment for invoice :' . $invoice->id, [
                "error" => "Transaction failed with error message: ",
                "error data" => $e->getMessage()
            ]);

            return false;
        }
    }

}
