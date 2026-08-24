<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_usage_meters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->string('name');
            $table->string('code');
            $table->string('unit');
            $table->unsignedBigInteger('unit_price_minor');
            $table->char('currency', 3);
            $table->decimal('threshold', 16, 4)->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'code']);
        });
        Schema::create('billing_usage_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->foreignId('meter_id')->constrained('billing_usage_meters')->cascadeOnDelete();
            $table->string('event_key');
            $table->decimal('quantity', 16, 4);
            $table->unsignedBigInteger('unit_price_minor');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->timestamp('occurred_at')->index();
            $table->foreignId('corrects_id')->nullable()->constrained('billing_usage_records')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['meter_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_usage_records');
        Schema::dropIfExists('billing_usage_meters');
    }
};
