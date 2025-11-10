<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Add branch_id column (nullable initially, will be made NOT NULL after data migration)
            $table->uuid('branch_id')->nullable()->after('shop_id');

            // Add indexes for branch-based queries
            $table->index(['branch_id', 'created_at']);
            $table->index(['shop_id', 'branch_id', 'movement_type']);
        });

        // Update movement_type enum to include branch transfer types
        DB::statement("
            ALTER TABLE stock_movements
            MODIFY COLUMN movement_type ENUM(
                'sale', 'purchase', 'return_from_customer', 'return_to_supplier',
                'adjustment_increase', 'adjustment_decrease', 'damage', 'theft',
                'expired', 'transfer_out', 'transfer_in', 'stocktake', 'opening_balance',
                'branch_transfer_out', 'branch_transfer_in'
            ) NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert movement_type enum to original values
        DB::statement("
            ALTER TABLE stock_movements
            MODIFY COLUMN movement_type ENUM(
                'sale', 'purchase', 'return_from_customer', 'return_to_supplier',
                'adjustment_increase', 'adjustment_decrease', 'damage', 'theft',
                'expired', 'transfer_out', 'transfer_in', 'stocktake', 'opening_balance'
            ) NOT NULL
        ");

        Schema::table('stock_movements', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['branch_id', 'created_at']);
            $table->dropIndex(['shop_id', 'branch_id', 'movement_type']);

            // Drop column
            $table->dropColumn('branch_id');
        });
    }
};
