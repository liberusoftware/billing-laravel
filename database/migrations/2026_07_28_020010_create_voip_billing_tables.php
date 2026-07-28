<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('did_numbers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voip_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->string('country_code', 2);
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('available');
            $table->timestamps();
            $table->unique(['team_id', 'number']);
        });

        Schema::create('call_rate_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
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
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voip_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('call_rate_rule_id')->nullable()->constrained()->nullOnDelete();
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
            $table->index(['team_id', 'started_at']);
        });

        Schema::create('voip_fraud_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voip_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('call_detail_record_id')->nullable()->constrained()->nullOnDelete();
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
        Schema::dropIfExists('did_numbers');
    }
};
