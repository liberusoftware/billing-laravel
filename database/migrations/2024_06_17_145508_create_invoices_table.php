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
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->string('invoice_number')->unique();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'paid', 'overdue']);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('status_history')->nullable();
            $table->decimal('late_fee_amount', 10, 2)->default(0);
            $table->timestamp('last_late_fee_date')->nullable();
            $table->boolean('upcoming_reminder_sent')->nullable();
            $table->integer('reminder_count')->nullable();
            $table->timestamp('last_reminder_date')->nullable();
            $table->integer('discount_id')->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->integer('parent_invoice_id')->nullable();
            $table->boolean('is_installment')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
