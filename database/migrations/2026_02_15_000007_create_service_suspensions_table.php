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
        Schema::create('service_suspensions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->enum('reason', ['overdue_payment', 'manual', 'terms_violation', 'fraud'])->default('overdue_payment');
            $table->text('notes')->nullable();
            $table->timestamp('suspended_at');
            $table->timestamp('unsuspended_at')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('unsuspended_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['subscription_id', 'is_active']);
            $table->index('suspended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_suspensions');
    }
};
