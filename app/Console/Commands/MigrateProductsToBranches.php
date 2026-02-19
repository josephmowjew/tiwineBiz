<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductBranch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateProductsToBranches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:migrate-to-branches
                            {--dry-run : Show what would be migrated without actually doing it}
                            {--shop-id= : Only migrate products for a specific shop}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing products to product_branches table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $shopId = $this->option('shop-id');

        $this->info('Starting product migration...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        if ($shopId) {
            $this->info("Only migrating products for shop: {$shopId}");
        }

        // Build query
        $query = Product::whereNotNull('branch_id');
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $products = $query->get();
        $totalProducts = $products->count();
        $migratedCount = 0;
        $skippedCount = 0;

        $this->info("Found {$totalProducts} products to process");

        DB::beginTransaction();

        try {
            foreach ($products as $product) {
                // Check if product_branch already exists
                $existing = ProductBranch::where('product_id', $product->id)
                    ->where('branch_id', $product->branch_id)
                    ->first();

                if ($existing) {
                    $this->warn("  SKIP: ProductBranch already exists for product {$product->name} (branch: {$product->branch_id})");
                    $skippedCount++;

                    continue;
                }

                $this->info("  Processing: {$product->name} (Branch: {$product->branch_id}, Qty: {$product->quantity})");

                if (! $dryRun) {
                    ProductBranch::create([
                        'shop_id' => $product->shop_id,
                        'product_id' => $product->id,
                        'branch_id' => $product->branch_id,
                        'quantity' => $product->quantity ?? 0,
                        'min_stock_level' => $product->min_stock_level ?? 10,
                        'max_stock_level' => $product->max_stock_level ?? 1000,
                        'reorder_point' => $product->reorder_point ?? 5,
                        'cost_price' => $product->cost_price,
                        'landing_cost' => $product->landing_cost,
                        'is_active' => true,
                    ]);
                }

                $migratedCount++;

                // Show progress every 100 products
                if ($migratedCount % 100 === 0) {
                    $this->info("Progress: {$migratedCount}/{$totalProducts}");
                }
            }

            if (! $dryRun) {
                DB::commit();
                $this->info("✅ Successfully migrated {$migratedCount} products");
            } else {
                DB::rollBack();
                $this->info("📊 Dry run complete: Would migrate {$migratedCount} products");
            }

            $this->info("Skipped: {$skippedCount} (already migrated)");

            // Show summary
            $this->newLine();
            $this->info('=== Summary ===');
            $this->info("Total products processed: {$totalProducts}");
            $this->info("Products to migrate: {$migratedCount}");
            $this->info("Products skipped: {$skippedCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Migration failed: {$e->getMessage()}");
            Log::error('Product migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }

        return 0;
    }
}
