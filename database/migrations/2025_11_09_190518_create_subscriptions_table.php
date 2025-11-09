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
        Schema::create('subscriptions', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Relationships
            $table->uuid('shop_id');

            // Plan
            $table->enum('plan', ['free', 'business', 'professional', 'enterprise']);
            $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly');

            // Pricing
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('MWK');

            // Status
            $table->enum('status', ['active', 'cancelled', 'suspended', 'grace_period', 'expired'])->default('active');

            // Dates
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');

            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);

            // Trial
            $table->timestamp('trial_ends_at')->nullable();

            // Features & Limits
            $table->json('features')->nullable();
            $table->json('limits')->nullable();

            // Audit
            $table->timestamps();

            // Indexes
            $table->index(['shop_id', 'status']);
            $table->index('current_period_end');
            $table->unique(['shop_id', 'started_at']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
