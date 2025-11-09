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
        Schema::create('subscription_payments', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Relationships
            $table->uuid('subscription_id');
            $table->uuid('shop_id');

            // Payment Identity
            $table->string('payment_number', 50);
            $table->string('invoice_number', 50)->nullable();

            // Amount
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('MWK');

            // Payment Method
            $table->enum('payment_method', ['airtel_money', 'tnm_mpamba', 'bank_transfer', 'cash'])->nullable();
            $table->string('transaction_reference', 100)->nullable();

            // Status
            $table->enum('status', ['pending', 'confirmed', 'failed', 'refunded'])->default('pending');

            // Period covered
            $table->timestamp('period_start');
            $table->timestamp('period_end');

            // Dates
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->uuid('confirmed_by')->nullable();

            // Audit
            $table->timestamps();

            // Indexes
            $table->unique(['shop_id', 'payment_number']);
            $table->index(['subscription_id', 'created_at']);
            $table->index(['shop_id', 'status']);

            // Foreign Keys
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
