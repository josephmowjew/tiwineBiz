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
        Schema::create('shop_users', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Relationships
            $table->uuid('shop_id');
            $table->uuid('user_id');
            $table->uuid('role_id');

            // Status
            $table->boolean('is_active')->default(true);
            $table->uuid('invited_by')->nullable();
            $table->string('invitation_token')->nullable();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();

            // Audit
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_accessed_at')->nullable();

            // Indexes
            $table->unique(['shop_id', 'user_id']);
            $table->index(['shop_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index('invitation_token');

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_users');
    }
};
