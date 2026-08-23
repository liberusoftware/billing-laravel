<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', static function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('status')->nullable();
            $table->float('refunded_amount')->nullable();
            $table->string('refund_reason')->nullable();
            $table->string('reconciliation_status')->nullable();
            $table->text('reconciliation_notes')->nullable();
            $table->string('stripe_token')->nullable();
            $table->string('square_token')->nullable();
            $table->string('google_pay_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn([
                'status',
                'refunded_amount',
                'refund_reason',
                'reconciliation_status',
                'reconciliation_notes',
                'stripe_token',
                'square_token',
                'google_pay_token',
            ]);
        });
    }
};
