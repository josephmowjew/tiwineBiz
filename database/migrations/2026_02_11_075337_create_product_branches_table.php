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
        Schema::create('product_branches', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Relationships
            $table->uuid('product_id');
            $table->uuid('branch_id');

            // Stock Information
            $table->decimal('quantity', 10, 3)->default(0);
            $table->decimal('min_stock_level', 10, 3)->nullable()->comment('Alert when stock falls below this level');
            $table->decimal('max_stock_level', 10, 3)->nullable()->comment('Alert when stock exceeds this level');
            $table->decimal('reorder_point', 10, 3)->nullable();

            // Location-specific pricing (optional)
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('landing_cost', 15, 2)->nullable();

            // Metadata
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint: one product can only have one record per branch
            $table->unique(['product_id', 'branch_id'], 'unique_product_branch');

            // Indexes
            $table->index(['shop_id', 'branch_id']);
            $table->index(['product_id']);
            $table->index(['branch_id']);
            $table->index(['shop_id', 'product_id']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_branches');
    }
};
