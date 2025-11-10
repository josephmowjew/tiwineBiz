<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make branch_id NOT NULL on sales
        DB::statement('ALTER TABLE sales MODIFY COLUMN branch_id CHAR(36) NOT NULL');

        // Make branch_id NOT NULL on stock_movements
        DB::statement('ALTER TABLE stock_movements MODIFY COLUMN branch_id CHAR(36) NOT NULL');

        // Note: customers.branch_id remains nullable (shop-level customers allowed)
        // Note: product_batches.branch_id remains nullable (shop-level batches allowed)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert branch_id to nullable
        DB::statement('ALTER TABLE sales MODIFY COLUMN branch_id CHAR(36) NULL');
        DB::statement('ALTER TABLE stock_movements MODIFY COLUMN branch_id CHAR(36) NULL');
    }
};
