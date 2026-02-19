<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Shop;
use App\Models\ShopUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates a cashier user for Mufash Electronics.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Get Mufash Electronics shop
            $shop = Shop::where('name', 'Mufasah Electronics')->first();

            if (! $shop) {
                $this->command->error('Mufasah Electronics shop not found. Please run AdminSeeder first.');

                return;
            }

            // Get cashier role
            $cashierRole = Role::where('name', 'cashier')
                ->where('is_system_role', true)
                ->first();

            if (! $cashierRole) {
                $this->command->error('Cashier role not found. Please run RoleSeeder first.');

                return;
            }

            // Create or update cashier user
            $cashier = User::updateOrCreate(
                ['email' => 'cashier@mufashelectronics.com'],
                [
                    'name' => 'Cashier User',
                    'password_hash' => 'Cashier@2026',
                    'phone' => '+265888123456',
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'preferred_language' => 'en',
                    'timezone' => 'Africa/Blantyre',
                ]
            );

            // Attach cashier to shop
            ShopUser::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'user_id' => $cashier->id,
                ],
                [
                    'role_id' => $cashierRole->id,
                    'is_active' => true,
                    'joined_at' => now(),
                ]
            );

            $this->command->info('Cashier user created successfully!');
            $this->command->info('Cashier Email: cashier@mufashelectronics.com');
            $this->command->info('Cashier Password: Cashier@2026');
        });
    }
}
