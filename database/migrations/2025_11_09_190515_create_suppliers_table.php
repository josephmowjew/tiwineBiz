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
        Schema::create('suppliers', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Supplier Information
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('supplier_code', 50)->nullable();

            // Contact Person
            $table->string('contact_person')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email')->nullable();

            // Company Contact
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Address
            $table->text('physical_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();

            // Financial
            $table->text('payment_terms')->nullable();
            $table->integer('credit_days')->default(0);
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_name', 100)->nullable();

            // Tax
            $table->string('tax_id', 50)->nullable();

            // Performance (updated via triggers)
            $table->integer('total_orders')->default(0);
            $table->decimal('total_order_value', 15, 2)->default(0.00);
            $table->integer('average_delivery_days')->nullable();
            $table->integer('reliability_score')->default(50);
            $table->date('last_order_date')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_preferred')->default(false);

            // Metadata
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            // Audit
            $table->timestamps();

            // Indexes
            $table->index(['shop_id', 'is_active']);
            $table->index('name');
            $table->index(['shop_id', 'is_preferred']);
            $table->unique(['shop_id', 'supplier_code']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
