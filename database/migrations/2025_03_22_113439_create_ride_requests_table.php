<?php

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
        Schema::create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->decimal('pickup_latitude', 10, 6);
            $table->decimal('pickup_longitude', 10, 6);
            $table->decimal('dest_latitude', 10, 6);
            $table->decimal('dest_longitude', 10, 6);
            $table->unsignedBigInteger('user_id');
            $table->boolean('isPending')->default(true);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
