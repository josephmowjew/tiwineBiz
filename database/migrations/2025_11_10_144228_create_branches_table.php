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
        Schema::create('branches', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Parent Relationship
            $table->uuid('shop_id');

            // Branch Information
            $table->string('name', 255);
            $table->string('code', 50);
            $table->enum('branch_type', ['main', 'satellite', 'warehouse', 'kiosk'])->default('satellite');

            // Contact Information
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();

            // Physical Address
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Management
            $table->uuid('manager_id')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Settings & Features (JSONB for flexibility)
            $table->json('settings')->nullable();
            $table->json('features')->nullable();

            // Audit
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['shop_id', 'code']);
            $table->index(['shop_id', 'is_active']);
            $table->index('manager_id');

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
