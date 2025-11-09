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
        Schema::create('sales', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Sale Identity
            $table->string('sale_number', 50);

            // Relationships
            $table->uuid('customer_id')->nullable();

            // Amounts (all in shop's default currency)
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);

            // Payment
            $table->decimal('amount_paid', 15, 2)->default(0.00);
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('change_given', 15, 2)->default(0.00);

            $table->enum('payment_status', ['paid', 'partial', 'pending', 'cancelled', 'refunded'])->default('pending');

            // Payment Methods (can be split)
            $table->json('payment_methods')->nullable()->comment('[{"method": "cash", "amount": 50000}]');

            // Currency (for multi-currency sales)
            $table->string('currency', 3)->default('MWK');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('amount_in_base_currency', 15, 2)->nullable();

            // MRA/EFD Integration
            $table->boolean('is_fiscalized')->default(false);
            $table->string('efd_device_id', 100)->nullable();
            $table->string('efd_receipt_number', 100)->nullable();
            $table->text('efd_qr_code')->nullable();
            $table->text('efd_fiscal_signature')->nullable();
            $table->timestamp('efd_transmitted_at')->nullable();
            $table->json('efd_response')->nullable();

            // Metadata
            $table->enum('sale_type', ['pos', 'whatsapp', 'phone_order', 'online'])->default('pos');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            // Timestamps
            $table->timestamp('sale_date')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            // Staff
            $table->uuid('served_by')->nullable();
            $table->uuid('shift_id')->nullable();

            // Cancellation/Refund
            $table->timestamp('cancelled_at')->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable();

            // Audit
            $table->timestamps();

            // Indexes (CRITICAL for performance)
            $table->unique(['shop_id', 'sale_number']);
            $table->index(['shop_id', 'sale_date']);
            $table->index('customer_id');
            $table->index(['shop_id', 'payment_status']);
            $table->index(['served_by', 'sale_date']);
            $table->index(['shop_id', 'is_fiscalized', 'sale_date']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('served_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
