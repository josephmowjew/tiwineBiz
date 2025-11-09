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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Relationships
            $table->uuid('purchase_order_id');
            $table->uuid('product_id')->nullable();

            // Product Info (snapshot)
            $table->string('product_name');
            $table->string('product_code', 100)->nullable();

            // Quantities
            $table->decimal('quantity_ordered', 10, 3);
            $table->decimal('quantity_received', 10, 3)->default(0.00);
            $table->string('unit', 20)->nullable();

            // Pricing
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);

            // Status
            $table->boolean('is_complete')->default(false);

            // Metadata
            $table->text('notes')->nullable();

            // Audit
            $table->timestamps();

            // Indexes
            $table->index('purchase_order_id');
            $table->index('product_id');

            // Foreign Keys
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
