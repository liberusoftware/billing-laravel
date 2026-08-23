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
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('product_service_id')->constrained('products_services')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('renewal_period')->default('monthly');
            $table->string('status')->default('active');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('last_billed_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('domain')->nullable();
            $table->string('domain_name')->nullable();
            $table->string('domain_registrar')->nullable();
            $table->timestamp('domain_expiration_date')->nullable();
            $table->json('scheduled_change')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
