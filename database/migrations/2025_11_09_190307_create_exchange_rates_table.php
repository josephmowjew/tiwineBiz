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
        Schema::create('exchange_rates', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Currency Pair
            $table->string('base_currency', 3)->default('MWK');
            $table->string('target_currency', 3);

            // Rates
            $table->decimal('official_rate', 10, 4);
            $table->decimal('street_rate', 10, 4)->nullable();
            $table->string('rate_used', 20)->default('official');

            // Validity
            $table->date('effective_date');
            $table->date('valid_until')->nullable();

            // Source
            $table->string('source', 100)->default('manual');

            // Audit
            $table->timestamp('created_at')->useCurrent();
            $table->uuid('created_by')->nullable();

            // Indexes
            $table->index(['target_currency', 'effective_date']);
            $table->index('target_currency');
            $table->unique(['target_currency', 'effective_date']);

            // Foreign Keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
