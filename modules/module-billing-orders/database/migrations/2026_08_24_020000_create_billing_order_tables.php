<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_order_quotes', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('team_id')->nullable()->index();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->string('quote_number')->unique();
            $t->char('currency', 3);
            $t->unsignedBigInteger('total_minor');
            $t->json('items');
            $t->timestamp('valid_until')->nullable();
            $t->string('status')->index();
            $t->timestamps();
        });
        Schema::create('billing_order_carts', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('team_id')->nullable()->index();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->char('currency', 3);
            $t->json('items');
            $t->timestamp('expires_at')->nullable();
            $t->string('status')->index();
            $t->timestamps();
        });
        Schema::create('billing_orders', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('team_id')->nullable()->index();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->unsignedBigInteger('quote_id')->nullable()->index();
            $t->string('order_number')->unique();
            $t->char('currency', 3);
            $t->unsignedBigInteger('subtotal_minor');
            $t->unsignedBigInteger('discount_minor')->default(0);
            $t->unsignedBigInteger('tax_minor')->default(0);
            $t->unsignedBigInteger('total_minor');
            $t->string('status')->index();
            $t->string('fraud_status')->index();
            $t->json('agreement')->nullable();
            $t->json('change_orders')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_orders');
        Schema::dropIfExists('billing_order_carts');
        Schema::dropIfExists('billing_order_quotes');
    }
};
