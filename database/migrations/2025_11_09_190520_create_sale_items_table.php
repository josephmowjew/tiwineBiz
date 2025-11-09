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
        Schema::create('sale_items', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Relationships
            $table->uuid('sale_id');
            $table->uuid('product_id');
            $table->uuid('batch_id')->nullable();

            // Product Snapshot (INTENTIONAL DENORMALIZATION for history)
            $table->string('product_name');
            $table->string('product_name_chichewa')->nullable();
            $table->string('product_sku', 100)->nullable();

            // Quantities
            $table->decimal('quantity', 10, 3);
            $table->string('unit', 20)->nullable();

            // Pricing
            $table->decimal('unit_cost', 15, 2)->nullable()->comment('For profit calculation');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('discount_percentage', 5, 2)->default(0.00);

            // Tax
            $table->boolean('is_taxable')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);

            // Amounts
            $table->decimal('subtotal', 15, 2);
            $table->decimal('total', 15, 2);

            // Metadata
            $table->text('notes')->nullable();

            // Audit
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('sale_id');
            $table->index(['product_id', 'created_at']);
            $table->index('batch_id');

            // Foreign Keys
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('batch_id')->references('id')->on('product_batches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
