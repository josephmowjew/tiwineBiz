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
        Schema::table('product_batches', function (Blueprint $table) {
            // Add shop_id (currently missing - will be populated from product relationship)
            $table->uuid('shop_id')->nullable()->after('product_id');

            // Add branch_id (nullable - batches can be shop-level before distribution to branches)
            $table->uuid('branch_id')->nullable()->after('shop_id');

            // Add indexes
            $table->index(['shop_id', 'is_depleted']);
            $table->index(['branch_id', 'is_depleted']);
            $table->index(['shop_id', 'branch_id', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['shop_id', 'is_depleted']);
            $table->dropIndex(['branch_id', 'is_depleted']);
            $table->dropIndex(['shop_id', 'branch_id', 'expiry_date']);

            // Drop columns
            $table->dropColumn(['shop_id', 'branch_id']);
        });
    }
};
