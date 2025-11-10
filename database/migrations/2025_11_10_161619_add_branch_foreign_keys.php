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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('restrict');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('restrict');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('cascade');

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropForeign(['branch_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
    }
};
