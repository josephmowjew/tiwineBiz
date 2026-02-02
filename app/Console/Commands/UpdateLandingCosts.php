<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateLandingCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-landing-costs {--force : Skip confirmation and update automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update landing_cost for products that currently have landing_cost = 0 to equal their cost_price';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Updating product landing costs...');

        // Find products with landing_cost = 0
        $products = Product::where('landing_cost', 0)->get();

        if ($products->isEmpty()) {
            $this->warn('No products found with landing_cost = 0');

            return self::SUCCESS;
        }

        $this->info("Found {$products->count()} products with landing_cost = 0");

        if (! $this->option('force') && ! $this->confirm("Do you want to update landing_cost to equal cost_price for these products?")) {
            $this->warn('Operation cancelled');

            return self::SUCCESS;
        }

        // Show preview of first 5 products
        if (! $this->option('force')) {
            $this->table(
                ['Product', 'Cost Price', 'Current Landing Cost', 'New Landing Cost'],
                $products->take(5)->map(fn ($product) => [
                    $product->name,
                    number_format($product->cost_price, 2),
                    number_format($product->landing_cost, 2),
                    number_format($product->cost_price, 2),
                ])
            );

            if (! $this->confirm('Continue with the update?', true)) {
                $this->warn('Operation cancelled');

                return self::SUCCESS;
            }
        }

        // Update in a transaction for safety
        DB::beginTransaction();

        try {
            $updated = Product::where('landing_cost', 0)->update([
                'landing_cost' => DB::raw('cost_price'),
            ]);

            DB::commit();

            $this->info("✅ Successfully updated {$updated} products");
            $this->info('Landing costs now equal cost prices. You can manually edit products to add additional expenses (shipping, customs, MRA, etc.)');

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("❌ Error updating products: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
