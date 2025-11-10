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
        Schema::create('branch_user', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Relationships
            $table->uuid('branch_id');
            $table->uuid('user_id');
            $table->uuid('role_id');

            // Status & Access Control
            $table->boolean('is_active')->default(true);
            $table->boolean('can_view_reports')->default(false);
            $table->boolean('can_manage_stock')->default(false);
            $table->boolean('can_process_sales')->default(true);
            $table->boolean('can_manage_customers')->default(true);

            // Granular Permissions (JSONB for flexibility)
            $table->json('permissions')->nullable();

            // Audit
            $table->uuid('assigned_by')->nullable();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('last_accessed_at')->nullable();

            // Indexes
            $table->unique(['branch_id', 'user_id']);
            $table->index(['branch_id', 'is_active']);
            $table->index(['user_id', 'is_active']);

            // Foreign Keys
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
