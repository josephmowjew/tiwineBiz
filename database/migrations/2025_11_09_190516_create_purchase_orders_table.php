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
        Schema::create('purchase_orders', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Relationships
            $table->uuid('supplier_id');

            // PO Identity
            $table->string('po_number', 50);

            // Amounts
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0.00);

            // Import Costs (for cross-border purchases)
            $table->decimal('freight_cost', 15, 2)->default(0.00);
            $table->decimal('insurance_cost', 15, 2)->default(0.00);
            $table->decimal('customs_duty', 15, 2)->default(0.00);
            $table->decimal('clearing_fee', 15, 2)->default(0.00);
            $table->decimal('transport_cost', 15, 2)->default(0.00);
            $table->decimal('other_charges', 15, 2)->default(0.00);

            $table->decimal('total_amount', 15, 2);

            // Currency
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('amount_in_base_currency', 15, 2)->nullable();

            // Status
            $table->enum('status', [
                'draft', 'sent', 'confirmed', 'in_transit', 'at_border',
                'clearing', 'received', 'partial', 'cancelled',
            ])->default('draft');

            // Dates
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();

            // Shipping
            $table->string('shipping_method', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();

            // Border/Customs (Malawi-specific)
            $table->enum('border_point', [
                'Mwanza', 'Dedza', 'Songwe', 'Mchinji', 'Muloza',
                'Chiponde', 'Nakonde', 'Karonga', 'Chilumba', 'Other',
            ])->nullable();
            $table->string('clearing_agent_name')->nullable();
            $table->string('clearing_agent_phone', 20)->nullable();
            $table->string('customs_entry_number', 100)->nullable();

            // Documents (stored as JSON array of URLs)
            $table->json('documents')->nullable()->comment('[{"type": "invoice", "url": "https://..."}]');

            // Metadata
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            // Audit
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamps();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();

            // Indexes
            $table->unique(['shop_id', 'po_number']);
            $table->index(['shop_id', 'status', 'order_date']);
            $table->index(['supplier_id', 'order_date']);
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'expected_delivery_date']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
