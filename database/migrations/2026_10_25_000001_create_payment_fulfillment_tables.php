<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('domain_renewals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained();
            $table->foreignId('subscription_id')->constrained();
            $table->string('domain');
            $table->string('registrar');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('subscription_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('invoice_id')->unique()->constrained();
            $table->foreignId('target_product_service_id')->constrained('products_services');
            $table->string('type');
            $table->string('status')->default('pending')->index();
            $table->text('last_error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('hosting_fulfillments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained();
            $table->foreignId('subscription_id')->constrained();
            $table->foreignId('invoice_id')->constrained();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_fulfillments');
        Schema::dropIfExists('subscription_changes');
        Schema::dropIfExists('domain_renewals');
    }
};
