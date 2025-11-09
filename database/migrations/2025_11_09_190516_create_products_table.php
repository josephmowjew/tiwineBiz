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
        Schema::create('products', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Product Identity
            $table->string('name');
            $table->string('name_chichewa')->nullable();
            $table->text('description')->nullable();

            // Product Codes (multiple identification methods)
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('manufacturer_code', 100)->nullable();

            // Categorization
            $table->uuid('category_id')->nullable();

            // Pricing (stored in shop's default currency, typically MWK)
            $table->decimal('cost_price', 15, 2)->default(0.00);
            $table->decimal('selling_price', 15, 2);
            $table->decimal('min_price', 15, 2)->nullable();

            // Multi-currency (for imported products)
            $table->string('base_currency', 3)->default('MWK');
            $table->decimal('base_currency_price', 15, 2)->nullable();
            $table->json('last_exchange_rate_snapshot')->nullable()->comment('{"USD": 1740, "ZAR": 95} at time of pricing');

            // Inventory (CONTROLLED DENORMALIZATION)
            $table->decimal('quantity', 10, 3)->default(0.00)->comment('Denormalized from stock_movements for performance');
            $table->enum('unit', ['piece', 'kg', 'g', 'liter', 'ml', 'meter', 'cm', 'box', 'dozen'])->default('piece');

            // Stock Management
            $table->decimal('min_stock_level', 10, 3)->default(0.00);
            $table->decimal('max_stock_level', 10, 3)->nullable();
            $table->decimal('reorder_point', 10, 3)->nullable();
            $table->decimal('reorder_quantity', 10, 3)->nullable();

            // Physical Location
            $table->string('storage_location', 100)->nullable();
            $table->string('shelf', 50)->nullable();
            $table->string('bin', 50)->nullable();

            // Tax
            $table->boolean('is_vat_applicable')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(16.5);
            $table->enum('tax_category', ['standard', 'zero_rated', 'exempt'])->default('standard');

            // Supplier
            $table->uuid('primary_supplier_id')->nullable();

            // Attributes (flexible key-value for product-specific data)
            $table->json('attributes')->nullable()->comment('{"color": "blue", "size": "XL", "warranty_months": 12}');

            // Media
            $table->json('images')->nullable()->comment('[{"url": "https://...", "is_primary": true}]');

            // Tracking
            $table->boolean('track_batches')->default(false);
            $table->boolean('track_serial_numbers')->default(false);
            $table->boolean('has_expiry')->default(false);

            // Performance Metrics (updated via triggers)
            $table->decimal('total_sold', 10, 3)->default(0.00);
            $table->decimal('total_revenue', 15, 2)->default(0.00);
            $table->timestamp('last_sold_at')->nullable();
            $table->timestamp('last_restocked_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('discontinued_at')->nullable();

            // Audit
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            // Indexes (critical for performance)
            $table->index(['shop_id', 'is_active', 'is_deleted']);
            $table->index('barcode');
            $table->index(['shop_id', 'sku']);
            $table->index(['shop_id', 'category_id']);
            $table->index(['shop_id', 'quantity']); // For low stock queries
            $table->index('name');
            $table->index('primary_supplier_id');

            // Unique Constraints
            $table->unique(['shop_id', 'sku']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('primary_supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
