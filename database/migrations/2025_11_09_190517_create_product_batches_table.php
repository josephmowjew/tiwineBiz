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
        Schema::create('product_batches', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Relationships
            $table->uuid('product_id');
            $table->uuid('purchase_order_id')->nullable();
            $table->uuid('supplier_id')->nullable();

            // Batch Identity
            $table->string('batch_number', 100);
            $table->string('lot_number', 100)->nullable();

            // Quantities
            $table->decimal('initial_quantity', 10, 3);
            $table->decimal('remaining_quantity', 10, 3);

            // Cost (for FIFO costing)
            $table->decimal('unit_cost', 15, 2);
            $table->string('currency', 3)->default('MWK');

            // Landed Cost Breakdown (for imported goods)
            $table->decimal('product_cost', 15, 2)->nullable();
            $table->decimal('freight_cost', 15, 2)->nullable();
            $table->decimal('customs_duty', 15, 2)->nullable();
            $table->decimal('clearing_fee', 15, 2)->nullable();
            $table->decimal('other_costs', 15, 2)->nullable();
            $table->decimal('total_landed_cost', 15, 2)->nullable();

            // Dates
            $table->date('purchase_date');
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Status
            $table->boolean('is_depleted')->default(false);

            // Metadata
            $table->text('notes')->nullable();

            // Audit
            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'is_depleted']);
            $table->index('expiry_date');
            $table->index('purchase_order_id');
            $table->index(['product_id', 'purchase_date']); // For FIFO

            // Foreign Keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
