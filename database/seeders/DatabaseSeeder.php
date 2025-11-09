<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Always seed system data (roles, exchange rates, categories)
        $this->call([
            RoleSeeder::class,
            ExchangeRateSeeder::class,
            CategorySeeder::class,
        ]);

        // Seed demo data only in local/development environments
        if (App::environment(['local', 'development'])) {
            $this->call([
                DemoDataSeeder::class,
            ]);
        }
    }
}
