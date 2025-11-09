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
        Schema::create('categories', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation (NULL means system-wide category)
            $table->uuid('shop_id')->nullable();

            // Category Information
            $table->string('name', 100);
            $table->string('name_chichewa', 100)->nullable();
            $table->string('slug', 100);
            $table->text('description')->nullable();

            // Hierarchy (Materialized Path pattern)
            $table->uuid('parent_id')->nullable();
            $table->string('path')->nullable()->comment('Materialized path: /electronics/phones/');
            $table->integer('depth')->default(0);

            // Ordering
            $table->integer('display_order')->default(0);

            // Metadata
            $table->string('icon', 50)->nullable();
            $table->string('color', 7)->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            // Audit
            $table->timestamps();

            // Constraints
            $table->unique(['shop_id', 'slug']);

            // Indexes
            $table->index(['shop_id', 'is_active']);
            $table->index('parent_id');
            $table->index('path');

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
