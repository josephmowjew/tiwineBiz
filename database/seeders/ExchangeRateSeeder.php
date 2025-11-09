<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates initial exchange rates for Malawi (MWK) to major currencies.
     * Rates are approximate as of November 2024.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // MWK to USD
            ExchangeRate::create([
                'base_currency' => 'MWK',
                'target_currency' => 'USD',
                'official_rate' => 1740.00,
                'street_rate' => 1900.00,
                'rate_used' => 'official',
                'effective_date' => now()->toDateString(),
                'source' => 'manual',
            ]);

            // MWK to ZAR (South African Rand)
            ExchangeRate::create([
                'base_currency' => 'MWK',
                'target_currency' => 'ZAR',
                'official_rate' => 95.00,
                'street_rate' => 102.00,
                'rate_used' => 'official',
                'effective_date' => now()->toDateString(),
                'source' => 'manual',
            ]);

            // MWK to GBP (British Pound)
            ExchangeRate::create([
                'base_currency' => 'MWK',
                'target_currency' => 'GBP',
                'official_rate' => 2200.00,
                'street_rate' => 2350.00,
                'rate_used' => 'official',
                'effective_date' => now()->toDateString(),
                'source' => 'manual',
            ]);

            // MWK to EUR (Euro)
            ExchangeRate::create([
                'base_currency' => 'MWK',
                'target_currency' => 'EUR',
                'official_rate' => 1850.00,
                'street_rate' => 1950.00,
                'rate_used' => 'official',
                'effective_date' => now()->toDateString(),
                'source' => 'manual',
            ]);
        });
    }
}
