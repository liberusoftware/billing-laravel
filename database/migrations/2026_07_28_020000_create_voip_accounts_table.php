<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voip_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform');
            $table->string('status')->default('pending');
            $table->string('sip_username');
            $table->text('sip_secret');
            $table->string('caller_id')->nullable();
            $table->decimal('credit_limit', 12, 4)->nullable();
            $table->decimal('current_usage_cost', 12, 4)->default(0);
            $table->unsignedInteger('max_concurrent_calls')->default(1);
            $table->boolean('international_enabled')->default(false);
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('platform_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'sip_username']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voip_accounts');
    }
};
