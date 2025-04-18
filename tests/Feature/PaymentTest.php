<?php

namespace Tests\Feature;

use App\Interfaces\Services\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\RideRequest;
use App\Models\User;
use App\Services\Payments\ZarinpalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_payment_link_for_unpaid_invoice()
    {
        // ایجاد کاربر و لاگین
        $user = User::factory()->create();
        $this->actingAs($user, 'user');

        // ساخت RideRequest و Invoice مرتبط
        $rideRequest = RideRequest::factory()->create([
            'cost' => 11000
        ]);
        $invoice     = Invoice::factory()->create([
            'ride_request_id' => $rideRequest->id,
            'user_id'         => $user->id,
            'isPaid'          => false,
            'amount'          => $rideRequest->cost,
        ]);

        // ماک کردن PaymentService
        $mockPaymentService = Mockery::mock(PaymentGatewayInterface::class);
        $mockPaymentService
            ->shouldReceive('request')
            ->once()
            ->withArgs(function ($passedInvoice, $data) use ($invoice, $user) {
                return $passedInvoice->id === $invoice->id && $data['email'] === $user->email;
            })
            ->andReturn([
                'status'      => true,
                'payment_url' => 'https://zarinpal.com/pg/StartPay/XXXX',
            ]);
        $this->app->instance(PaymentGatewayInterface::class, $mockPaymentService);

        // ارسال درخواست به کنترلر
        $response = $this->postJson(route('pay'), [
            'ride_request_id' => $rideRequest->id,
        ]);
        // بررسی پاسخ
        $response->assertStatus(200)
            ->assertJsonStructure(['payment_url'])
            ->assertExactJson([
                'payment_url' => 'https://zarinpal.com/pg/StartPay/XXXX',
            ]);
    }

    public function test_pay_returns_404_if_no_unpaid_invoice()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'user');

        $rideRequest = RideRequest::factory()->create([
            'cost' => 11000
        ]);
        Invoice::factory()->create([
            'ride_request_id' => $rideRequest->id,
            'user_id'         => $user->id,
            'isPaid'          => true, // قبلاً پرداخت شده
            'amount'          => $rideRequest->cost,
        ]);

        $response = $this->postJson(route('pay'), [
            'ride_request_id' => $rideRequest->id,
        ]);

        $response->assertStatus(404)
            ->assertExactJson(['message' => 'No unpaid ride request!']);
    }

    public function test_pay_returns_400_if_payment_request_fails()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'user');

        $rideRequest = RideRequest::factory()->create([
            'cost' => 11000
        ]);
        $invoice     = Invoice::factory()->create([
            'ride_request_id' => $rideRequest->id,
            'user_id'         => $user->id,
            'isPaid'          => false,
            'amount'          => $rideRequest->cost,
        ]);

        $mockPaymentService = \Mockery::mock(PaymentGatewayInterface::class);
        $mockPaymentService
            ->shouldReceive('request')
            ->once()
            ->andReturn([
                'status'  => false,
                'message' => 'Something went wrong!',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockPaymentService);

        $response = $this->postJson(route('pay'), [
            'ride_request_id' => $rideRequest->id,
        ]);

        $response->assertStatus(400)
            ->assertExactJson(['message' => 'Something went wrong!']);
    }

    public function test_user_can_verify_payment_successfully()
    {
        // ایجاد کاربر و لاگین
        $user = User::factory()->create();
        $this->actingAs($user, 'user');

        // ساخت RideRequest و Invoice
        $rideRequest = RideRequest::factory()->create(['cost' => 11000]);
        $invoice = Invoice::factory()->create([
            'ride_request_id' => $rideRequest->id,
            'user_id'         => $user->id,
            'isPaid'          => false,
            'amount'          => $rideRequest->cost,
            'authority'       => 'A000000000000000000000000000jjrx31yv',
        ]);
        $isSandboxMode = config('services.zarinpal.sandbox');
        $sandboxPrefix = $isSandboxMode ? 'sandbox' : 'payment';
        // فیک کردن پاسخ HTTP زرین‌پال
        Http::fake([
            "https://{$sandboxPrefix}.zarinpal.com/pg/v4/payment/verify.json" => Http::response([
                'data' => [
                    'code'     => 100,
                    'ref_id'   => '1234567890',
                    'card_pan' => '4321 **** **** 1234',
                    'fee'      => 1000,
                ],
                'errors' => null,
            ], 200)
        ]);

        // ارسال ریکوئست به verify endpoint
        $response = $this->getJson(route('payment-verify', [
            'invoice'   => $invoice->id,
            'Authority' => $invoice->authority,
            'Status'    => 'OK',
        ]));

        // بررسی پاسخ
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transaction Completed',
            ]);

        $invoice->refresh();
        $this->assertTrue($invoice->isPaid);
    }
}
