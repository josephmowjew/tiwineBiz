<?php

use App\Models\Branch;
use App\Models\Shop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all shops with their default branches
        $shops = Shop::with('branches')->get();

        foreach ($shops as $shop) {
            // Get main branch or first branch for this shop
            $defaultBranch = $shop->branches()
                ->where('branch_type', 'main')
                ->first();

            if (! $defaultBranch) {
                $defaultBranch = $shop->branches()->first();
            }

            if (! $defaultBranch) {
                // echo "Warning: Shop '{$shop->name}' (ID: {$shop->id}) has no branches. Skipping...\n";
                continue;
            }

            // Backfill sales.branch_id
            DB::table('sales')
                ->where('shop_id', $shop->id)
                ->whereNull('branch_id')
                ->update(['branch_id' => $defaultBranch->id]);

            // Backfill stock_movements.branch_id
            DB::table('stock_movements')
                ->where('shop_id', $shop->id)
                ->whereNull('branch_id')
                ->update(['branch_id' => $defaultBranch->id]);

            // Note: customers.branch_id stays NULL (shop-level customers by default)
        }

        // Backfill product_batches.shop_id from products table
        $batches = DB::table('product_batches')
            ->whereNull('shop_id')
            ->get();

        foreach ($batches as $batch) {
            $product = DB::table('products')->where('id', $batch->product_id)->first();
            if ($product) {
                DB::table('product_batches')
                    ->where('id', $batch->id)
                    ->update(['shop_id' => $product->shop_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert backfill - set branch_id back to NULL
        DB::table('sales')->update(['branch_id' => null]);
        DB::table('stock_movements')->update(['branch_id' => null]);
        DB::table('product_batches')->update(['shop_id' => null, 'branch_id' => null]);
    }
};
