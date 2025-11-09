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
        Schema::create('shops', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Ownership
            $table->uuid('owner_id');

            // Business Information
            $table->string('name', 255);
            $table->string('business_type', 100)->nullable();
            $table->string('legal_name', 255)->nullable();
            $table->string('registration_number', 100)->nullable();

            // Tax Information (MRA)
            $table->string('tpin', 20)->nullable();
            $table->string('vrn', 20)->nullable();
            $table->boolean('is_vat_registered')->default(false);

            // Contact Information
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website', 255)->nullable();

            // Physical Address
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('country', 100)->default('Malawi');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Branding
            $table->text('logo_url')->nullable();
            $table->string('primary_color', 7)->nullable();

            // Financial Settings
            $table->string('default_currency', 3)->default('MWK');
            $table->integer('fiscal_year_start_month')->default(1);

            // Subscription
            $table->string('subscription_tier', 20)->default('free');
            $table->string('subscription_status', 20)->default('active');
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Feature Flags & Limits (JSONB for flexibility)
            $table->json('features')->nullable();
            $table->json('limits')->nullable();

            // Settings (shop-specific configurations)
            $table->json('settings')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->text('deactivation_reason')->nullable();

            // Audit
            $table->timestamps();

            // Indexes
            $table->index('owner_id');
            $table->index('tpin');
            $table->index(['subscription_tier', 'subscription_status']);

            // Foreign Keys
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
