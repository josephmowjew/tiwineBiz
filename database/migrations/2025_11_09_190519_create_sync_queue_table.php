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
        Schema::create('sync_queue', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');
            $table->uuid('user_id');

            // Entity
            $table->string('entity_type', 50)->comment('sale, product, payment, credit');
            $table->uuid('entity_id');
            $table->enum('action', ['create', 'update', 'delete']);

            // Data
            $table->json('data');

            // Metadata
            $table->timestamp('client_timestamp');
            $table->string('device_id', 100)->nullable();

            // Sync Status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'conflict'])->default('pending');

            // Processing
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('error_message')->nullable();

            // Priority (higher = more urgent)
            $table->integer('priority')->default(5);

            // Conflict Resolution
            $table->json('conflict_data')->nullable()->comment('Server version of conflicting data');
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->enum('resolution', ['client_wins', 'server_wins', 'merge', 'manual'])->nullable();

            // Timestamps
            $table->timestamps();
            $table->timestamp('processed_at')->nullable();

            // Indexes
            $table->index(['shop_id', 'status', 'priority', 'created_at']);
            $table->index(['entity_type', 'entity_id', 'shop_id']);
            $table->index(['priority', 'created_at']);
            $table->index(['shop_id', 'created_at']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
