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
        Schema::create('activity_logs', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id')->nullable();

            // Actor
            $table->uuid('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();

            // Action
            $table->string('action', 100)->comment('Examples: product.created, sale.completed, price.changed');

            // Entity
            $table->string('entity_type', 50)->nullable()->comment('product, sale, customer, user');
            $table->uuid('entity_id')->nullable();
            $table->string('entity_name')->nullable();

            // Changes (for updates)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 100)->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            // Timestamp (IMMUTABLE)
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['shop_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index(['shop_id', 'action', 'created_at']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
