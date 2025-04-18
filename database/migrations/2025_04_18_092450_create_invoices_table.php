<?php

use App\Enums\PaymentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ride_request_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->boolean('isPaid')->default(false);
            $table->enum('payment_type', [PaymentType::ONLINE->value, PaymentType::CASH->value])->default(PaymentType::ONLINE->value);
            $table->timestamp('paid_at')->nullable();
            $table->string('authority')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
