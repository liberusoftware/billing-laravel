<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_pricing_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('name');
            $table->string('pricing_model');
            $table->char('currency', 3);
            $table->unsignedBigInteger('unit_amount_minor')->default(0);
            $table->string('billing_interval')->nullable();
            $table->string('usage_unit')->nullable();
            $table->json('tiers')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->index();
            $table->timestamps();
        });
        Schema::create('billing_pricing_contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->foreignId('pricing_plan_id')->constrained('billing_pricing_plans');
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->json('terms')->nullable();
            $table->string('status')->index();
            $table->timestamps();
        });
        Schema::create('billing_pricing_discounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->string('code');
            $table->string('kind');
            $table->unsignedBigInteger('value');
            $table->char('currency', 3)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redemptions')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['team_id', 'code']);
        });
        Schema::create('billing_pricing_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->foreignId('pricing_plan_id')->constrained('billing_pricing_plans');
            $table->unsignedInteger('version');
            $table->json('payload');
            $table->timestamp('captured_at');
            $table->timestamps();
            $table->unique(['pricing_plan_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_pricing_snapshots');
        Schema::dropIfExists('billing_pricing_discounts');
        Schema::dropIfExists('billing_pricing_contracts');
        Schema::dropIfExists('billing_pricing_plans');
    }
};
