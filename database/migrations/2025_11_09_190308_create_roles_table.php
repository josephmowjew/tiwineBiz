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
        Schema::create('roles', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation (NULL = system role)
            $table->uuid('shop_id')->nullable();

            // Role Information
            $table->string('name', 50);
            $table->string('display_name', 100)->nullable();
            $table->text('description')->nullable();

            // Type
            $table->boolean('is_system_role')->default(false);

            // Permissions (JSON array)
            $table->json('permissions')->nullable();

            // Audit
            $table->timestamps();

            // Indexes
            $table->index('shop_id');
            $table->unique(['shop_id', 'name']);

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
