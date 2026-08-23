<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table): void {
            $table->id();
            $table->integer('invoice_id')->nullable();
            $table->integer('total_installments');
            $table->decimal('installment_amount', 10, 2);
            $table->string('frequency');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('next_due_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};
