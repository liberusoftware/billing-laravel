<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voip_accounts')) {
            return;
        }

        Schema::create('voip_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('subscription_id')->nullable()->index();
            $table->string('platform');
            $table->string('status')->default('pending')->index();
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
        });

        Schema::create('call_rate_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('name');
            $table->string('destination_prefix');
            $table->decimal('connection_fee', 12, 4)->default(0);
            $table->decimal('rate_per_minute', 12, 4);
            $table->unsignedInteger('billing_increment_seconds')->default(60);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['team_id', 'destination_prefix']);
        });

        Schema::create('call_detail_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('voip_account_id')->index();
            $table->unsignedBigInteger('call_rate_rule_id')->nullable()->index();
            $table->string('external_id');
            $table->string('source');
            $table->string('destination');
            $table->string('direction')->default('outbound');
            $table->timestamp('started_at');
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('billable_seconds')->default(0);
            $table->decimal('rated_cost', 12, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('disposition')->default('unknown');
            $table->timestamp('invoiced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['voip_account_id', 'external_id']);
        });

        Schema::create('voip_fraud_alerts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('voip_account_id')->index();
            $table->unsignedBigInteger('call_detail_record_id')->nullable()->index();
            $table->string('rule');
            $table->string('severity');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voip_fraud_alerts');
        Schema::dropIfExists('call_detail_records');
        Schema::dropIfExists('call_rate_rules');
        Schema::dropIfExists('voip_accounts');
    }
};
