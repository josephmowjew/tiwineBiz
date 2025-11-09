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
        Schema::create('credits', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary();

            // Tenant Isolation
            $table->uuid('shop_id');

            // Relationships
            $table->uuid('customer_id');
            $table->uuid('sale_id')->nullable();

            // Credit Identity
            $table->string('credit_number', 50);

            // Amounts
            $table->decimal('original_amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0.00);
            $table->decimal('balance', 15, 2);

            // Dates
            $table->date('issue_date');
            $table->date('due_date');

            // Payment Terms (Malawian context)
            $table->enum('payment_term', [
                'lero', 'mawa', 'sabata_imeneyi', 'malipiro_15', 'malipiro_30',
                'masabata_2', 'mwezi_umodzi', 'miyezi_2', 'miyezi_3', 'custom',
            ])->nullable();

            // Status
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'written_off', 'disputed'])->default('pending');

            // Reminders
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->date('next_reminder_date')->nullable();

            // Collection
            $table->integer('collection_attempts')->default(0);
            $table->timestamp('last_collection_attempt_at')->nullable();
            $table->integer('escalation_level')->default(0);

            // Metadata
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            // Audit
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('written_off_at')->nullable();
            $table->uuid('written_off_by')->nullable();
            $table->text('write_off_reason')->nullable();

            // Indexes
            $table->unique(['shop_id', 'credit_number']);
            $table->index(['shop_id', 'status', 'due_date']);
            $table->index(['customer_id', 'status']);
            $table->index(['shop_id', 'due_date', 'balance']);
            $table->index('next_reminder_date');

            // Foreign Keys
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('written_off_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
