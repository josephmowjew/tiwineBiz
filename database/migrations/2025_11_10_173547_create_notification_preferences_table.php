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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type'); // low_stock, sale_completed, payment_reminder, subscription_expiring, system_announcement
            $table->string('channel'); // database, mail, sms, push
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            // Unique constraint to prevent duplicate preferences
            $table->unique(['user_id', 'notification_type', 'channel'], 'notification_pref_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
