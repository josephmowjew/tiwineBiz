<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Shop;
use App\Models\ShopUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates the default admin user and Mufasah Electronics shop.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create or update the admin user
            $admin = User::updateOrCreate(
                ['email' => 'admin@mufashelectronics.com'],
                [
                    'name' => 'Admin User',
                    'password_hash' => 'Mufasah@2026', // Will be automatically hashed by the model cast
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'preferred_language' => 'en',
                    'timezone' => 'Africa/Blantyre',
                ]
            );

            // Create or update the Mufasah Electronics shop
            $shop = Shop::updateOrCreate(
                [
                    'owner_id' => $admin->id,
                    'name' => 'Mufasah Electronics',
                ],
                [
                    'business_type' => 'electronics',
                    'email' => 'admin@mufashelectronics.com',
                    'country' => 'Malawi',
                    'default_currency' => 'MWK',
                    'subscription_tier' => 'premium',
                    'subscription_status' => 'active',
                    'is_active' => true,
                ]
            );

            // Get the owner role
            $ownerRole = Role::where('name', 'owner')
                ->where('is_system_role', true)
                ->firstOrFail();

            // Create or update the shop_user association
            ShopUser::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'user_id' => $admin->id,
                ],
                [
                    'role_id' => $ownerRole->id,
                    'is_active' => true,
                    'joined_at' => now(),
                ]
            );

            $this->command->info('Admin user and Mufasah Electronics shop created successfully.');
            $this->command->info('Admin Email: admin@mufashelectronics.com');
            $this->command->info('Admin Password: Mufasah@2026');
        });
    }
}
