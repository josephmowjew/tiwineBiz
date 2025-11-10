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
        Schema::table('customers', function (Blueprint $table) {
            // Add branch_id column (nullable - allows both shop-level and branch-specific customers)
            // NULL = shop-level customer (visible to all branches)
            // NOT NULL = branch-specific customer
            $table->uuid('branch_id')->nullable()->after('shop_id');

            // Add indexes for branch-based queries
            $table->index(['branch_id', 'is_active']);
            $table->index(['shop_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['branch_id', 'is_active']);
            $table->dropIndex(['shop_id', 'branch_id']);

            // Drop column
            $table->dropColumn('branch_id');
        });
    }
};
