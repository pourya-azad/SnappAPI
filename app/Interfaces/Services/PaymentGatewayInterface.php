<?php

namespace App\Interfaces\Services;

use App\Models\Invoice;

interface PaymentGatewayInterface
{
    public function request(Invoice $invoice, array $metadata = []): array; // returns payment URL
    public function verify(Invoice $invoice, array $verifyData = []): bool;
}
