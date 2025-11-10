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
        Schema::table('sales', function (Blueprint $table) {
            // Add branch_id column (nullable initially, will be made NOT NULL after data migration)
            $table->uuid('branch_id')->nullable()->after('shop_id');

            // Add indexes for branch-based queries
            $table->index(['branch_id', 'sale_date']);
            $table->index(['shop_id', 'branch_id', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['branch_id', 'sale_date']);
            $table->dropIndex(['shop_id', 'branch_id', 'payment_status']);

            // Drop column
            $table->dropColumn('branch_id');
        });
    }
};
