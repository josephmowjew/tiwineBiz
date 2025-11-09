<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates system-wide roles that are available for all shops.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Owner role - Full access to all shop features
            Role::create([
                'name' => 'owner',
                'display_name' => 'Owner',
                'description' => 'Full access to all shop features and settings',
                'is_system_role' => true,
                'shop_id' => null,
                'permissions' => ['*'],
            ]);

            // Manager role - Manage most operations except sensitive settings
            Role::create([
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Manage products, sales, customers, and view reports',
                'is_system_role' => true,
                'shop_id' => null,
                'permissions' => [
                    'products.*',
                    'sales.*',
                    'customers.*',
                    'credits.*',
                    'inventory.*',
                    'reports.view',
                    'reports.export',
                    'users.view',
                    'users.invite',
                ],
            ]);

            // Cashier role - Process sales and basic operations
            Role::create([
                'name' => 'cashier',
                'display_name' => 'Cashier',
                'description' => 'Process sales and view inventory',
                'is_system_role' => true,
                'shop_id' => null,
                'permissions' => [
                    'products.view',
                    'sales.create',
                    'sales.view',
                    'customers.view',
                    'inventory.view',
                ],
            ]);

            // Accountant role - Financial operations and reports
            Role::create([
                'name' => 'accountant',
                'display_name' => 'Accountant',
                'description' => 'View financial reports and manage credits',
                'is_system_role' => true,
                'shop_id' => null,
                'permissions' => [
                    'products.view',
                    'sales.view',
                    'customers.view',
                    'credits.*',
                    'payments.*',
                    'reports.*',
                ],
            ]);
        });
    }
}
