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
        Schema::create('customers', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Identity
            $table->string('customer_number', 50)->nullable();
            $table->string('name');

            // Contact
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp_number', 20)->nullable();

            // Address
            $table->text('physical_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();

            // Credit Management (CONTROLLED DENORMALIZATION)
            $table->decimal('credit_limit', 15, 2)->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00)->comment('Denormalized from credits table');
            $table->decimal('total_spent', 15, 2)->default(0.00);
            $table->decimal('total_credit_issued', 15, 2)->default(0.00);
            $table->decimal('total_credit_collected', 15, 2)->default(0.00);

            // Trust Level (business logic)
            $table->enum('trust_level', ['trusted', 'monitor', 'restricted', 'new'])->default('new');
            $table->integer('payment_behavior_score')->default(50);

            // Statistics
            $table->integer('purchase_count')->default(0);
            $table->date('last_purchase_date')->nullable();
            $table->decimal('average_purchase_value', 15, 2)->nullable();

            // Preferences
            $table->enum('preferred_language', ['en', 'ny'])->default('en');
            $table->enum('preferred_contact_method', ['phone', 'whatsapp', 'sms', 'email'])->default('phone');

            // Metadata
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('blocked_at')->nullable();
            $table->text('block_reason')->nullable();

            // Audit
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['shop_id', 'customer_number']);
            $table->index(['shop_id', 'is_active']);
            $table->index(['shop_id', 'phone']);
            $table->index('name');
            $table->index(['shop_id', 'current_balance']);
            $table->index(['shop_id', 'trust_level']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
