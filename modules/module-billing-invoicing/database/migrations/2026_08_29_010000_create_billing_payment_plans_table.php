<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payment_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->foreignId('invoice_id')->constrained('billing_invoices');
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedInteger('total_installments');
            $table->unsignedBigInteger('installment_amount_minor');
            $table->string('frequency');
            $table->timestamp('start_at');
            $table->timestamp('next_due_at')->index();
            $table->unsignedInteger('generated_installments')->default(0);
            $table->string('status')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payment_plans');
    }
};
