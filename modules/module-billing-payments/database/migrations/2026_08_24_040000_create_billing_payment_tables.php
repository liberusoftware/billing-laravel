<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('type');
            $table->string('provider');
            $table->string('provider_reference')->nullable()->index();
            $table->string('display_name')->nullable();
            $table->string('last_four', 4)->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('billing_payment_mandates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('payment_method_id')->index();
            $table->string('provider');
            $table->string('provider_reference')->nullable()->index();
            $table->string('status')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('billing_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status')->index();
            $table->string('payment_method')->nullable();
            $table->string('gateway')->nullable()->index();
            $table->string('provider_reference')->nullable()->index();
            $table->timestamp('captured_at')->nullable();
            $table->unsignedBigInteger('refunded_minor')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('billing_payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('billing_payments')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('status')->index();
            $table->string('provider_reference')->nullable();
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('billing_payment_disputes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('billing_payments')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('status')->index();
            $table->string('provider_reference')->nullable();
            $table->string('reason');
            $table->json('evidence')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('billing_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('billing_payments')->cascadeOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('billing_payment_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('billing_payments')->cascadeOnDelete();
            $table->string('status')->index();
            $table->string('provider_reference')->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payment_reconciliations');
        Schema::dropIfExists('billing_payment_allocations');
        Schema::dropIfExists('billing_payment_disputes');
        Schema::dropIfExists('billing_payment_refunds');
        Schema::dropIfExists('billing_payments');
        Schema::dropIfExists('billing_payment_mandates');
        Schema::dropIfExists('billing_payment_methods');
    }
};
