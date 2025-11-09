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
        Schema::create('stock_movements', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Relationships
            $table->uuid('product_id');
            $table->uuid('batch_id')->nullable();

            // Movement Type
            $table->enum('movement_type', [
                'sale', 'purchase', 'return_from_customer', 'return_to_supplier',
                'adjustment_increase', 'adjustment_decrease', 'damage', 'theft',
                'expired', 'transfer_out', 'transfer_in', 'stocktake', 'opening_balance',
            ]);

            // Quantities (positive = increase, negative = decrease)
            $table->decimal('quantity', 10, 3);
            $table->decimal('quantity_before', 10, 3);
            $table->decimal('quantity_after', 10, 3);

            // Cost (for COGS calculation)
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();

            // References to source transaction
            $table->string('reference_type', 50)->nullable()->comment('sale, purchase_order, adjustment');
            $table->uuid('reference_id')->nullable();

            // Metadata
            $table->text('reason')->nullable()->comment('Required for adjustments, damage, theft');
            $table->text('notes')->nullable();

            // Location (for multi-location businesses)
            $table->string('from_location', 100)->nullable();
            $table->string('to_location', 100)->nullable();

            // Audit (IMMUTABLE - no updates allowed)
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes (critical for performance)
            $table->index(['shop_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index(['shop_id', 'movement_type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['created_by', 'created_at']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('product_batches')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
