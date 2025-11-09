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
        Schema::create('efd_transactions', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // EFD Device
            $table->string('efd_device_id', 100);
            $table->string('efd_device_serial', 100)->nullable();

            // Sale Reference
            $table->uuid('sale_id');

            // MRA Transaction Details
            $table->string('fiscal_receipt_number', 100);
            $table->integer('fiscal_day_counter')->nullable();
            $table->text('fiscal_signature');
            $table->text('qr_code_data')->nullable();
            $table->text('verification_url')->nullable();

            // Amounts
            $table->decimal('total_amount', 15, 2);
            $table->decimal('vat_amount', 15, 2)->nullable();

            // MRA Response
            $table->string('mra_response_code', 10)->nullable();
            $table->text('mra_response_message')->nullable();
            $table->json('mra_acknowledgement')->nullable();

            // Transmission
            $table->timestamp('transmitted_at');
            $table->enum('transmission_status', ['success', 'failed', 'pending', 'offline'])->default('success');

            // Retry Logic
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();

            // Audit (IMMUTABLE)
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->unique(['efd_device_id', 'fiscal_receipt_number']);
            $table->index(['shop_id', 'transmitted_at']);
            $table->index('sale_id');
            $table->index(['efd_device_id', 'fiscal_day_counter']);
            $table->index(['shop_id', 'transmission_status']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('efd_transactions');
    }
};
