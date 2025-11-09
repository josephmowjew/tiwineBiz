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
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Relationships
            $table->uuid('customer_id')->nullable();
            $table->uuid('credit_id')->nullable();
            $table->uuid('sale_id')->nullable();

            // Payment Identity
            $table->string('payment_number', 50);

            // Amount
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('MWK');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('amount_in_base_currency', 15, 2)->nullable();

            // Payment Method
            $table->enum('payment_method', [
                'cash', 'airtel_money', 'tnm_mpamba', 'nbs_bank',
                'standard_bank', 'fmb_bank', 'natswitch', 'bank_transfer', 'cheque', 'other',
            ]);

            // Transaction Details
            $table->string('transaction_reference', 100)->nullable();

            // Mobile Money Specific
            $table->json('mobile_money_details')->nullable()->comment('{"phone": "265999123456", "sender_name": "John Banda"}');

            // Bank Details
            $table->string('bank_name', 100)->nullable();
            $table->string('cheque_number', 50)->nullable();
            $table->date('cheque_date')->nullable();

            // Dates
            $table->timestamp('payment_date')->useCurrent();
            $table->timestamp('cleared_at')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->boolean('receipt_sent')->default(false);
            $table->timestamp('receipt_sent_at')->nullable();

            // Audit (IMMUTABLE - no updates after creation)
            $table->uuid('received_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->unique(['shop_id', 'payment_number']);
            $table->index(['shop_id', 'payment_date']);
            $table->index(['customer_id', 'payment_date']);
            $table->index('credit_id');
            $table->index(['shop_id', 'payment_method', 'payment_date']);
            $table->index('transaction_reference');

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('credit_id')->references('id')->on('credits')->onDelete('set null');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
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
