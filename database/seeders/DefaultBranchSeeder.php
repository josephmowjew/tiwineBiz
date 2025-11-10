<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class DefaultBranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating default branches for existing shops...');

        $shops = Shop::all();

        if ($shops->isEmpty()) {
            $this->command->warn('No shops found. Skipping branch creation.');

            return;
        }

        $createdCount = 0;

        foreach ($shops as $shop) {
            // Skip if shop already has branches
            if ($shop->branches()->exists()) {
                $this->command->info("Shop '{$shop->name}' already has branches. Skipping...");

                continue;
            }

            // Create default main branch
            $branch = Branch::create([
                'shop_id' => $shop->id,
                'name' => 'Main Branch',
                'code' => 'MAIN001',
                'branch_type' => 'main',
                'phone' => $shop->phone,
                'email' => $shop->email,
                'address' => $shop->address,
                'city' => $shop->city,
                'district' => $shop->district,
                'latitude' => $shop->latitude,
                'longitude' => $shop->longitude,
                'is_active' => true,
                'opened_at' => $shop->created_at ?? now(),
                'created_by' => $shop->owner_id,
            ]);

            $this->command->info("Created main branch for shop '{$shop->name}' (ID: {$branch->id})");
            $createdCount++;
        }

        $this->command->info("Successfully created {$createdCount} default branches.");
    }
}
