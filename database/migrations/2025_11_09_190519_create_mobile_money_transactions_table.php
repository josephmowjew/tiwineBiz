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
        Schema::create('mobile_money_transactions', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Provider
            $table->enum('provider', ['airtel_money', 'tnm_mpamba']);

            // Transaction Details
            $table->string('transaction_id', 100);
            $table->enum('transaction_type', ['c2b', 'b2c', 'b2b'])->nullable();

            // Parties
            $table->string('msisdn', 20);
            $table->string('sender_name')->nullable();
            $table->string('receiver_name')->nullable();

            // Amount
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('MWK');
            $table->decimal('transaction_fee', 15, 2)->default(0.00);

            // References
            $table->string('reference_type', 50)->nullable()->comment('sale, payment, subscription');
            $table->uuid('reference_id')->nullable();

            // Status
            $table->enum('status', ['pending', 'successful', 'failed', 'reversed'])->default('pending');

            // API Response (for debugging)
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            // Webhook
            $table->timestamp('webhook_received_at')->nullable();
            $table->json('webhook_payload')->nullable();

            // Dates
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();

            // Audit
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->unique(['provider', 'transaction_id']);
            $table->index(['shop_id', 'transaction_date']);
            $table->index(['provider', 'transaction_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['shop_id', 'status']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_money_transactions');
    }
};
