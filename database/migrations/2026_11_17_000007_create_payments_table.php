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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // MVP: satu pembayaran penuh per order (BUSINESS-RULES §6).
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->string('payment_number', 40)->unique();
            $table->string('idempotency_key', 100)->unique();
            $table->string('payment_method', 20);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('received_amount')->nullable();
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->string('status', 20)->default('PAID');
            $table->foreignId('processed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['payment_method', 'created_at']);
            $table->index(['processed_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
