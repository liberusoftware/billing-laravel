<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('connector_type');
            $table->string('provider');
            $table->string('name');
            $table->string('base_url');
            $table->text('access_token');
            $table->text('signing_secret')->nullable();
            $table->json('resource_mappings')->nullable();
            $table->json('event_subscriptions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'connector_type', 'name']);
        });

        Schema::create('external_sync_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_connection_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type');
            $table->string('local_id');
            $table->string('remote_id');
            $table->string('checksum')->nullable();
            $table->string('status')->default('synchronized');
            $table->timestamp('last_pushed_at')->nullable();
            $table->timestamp('last_pulled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(
                ['external_connection_id', 'resource_type', 'local_id'],
                'external_sync_local_unique'
            );
        });

        Schema::create('domain_event_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('event_name');
            $table->string('aggregate_type');
            $table->string('aggregate_id');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('occurred_at');
            $table->timestamp('published_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'status', 'occurred_at']);
        });

        Schema::create('domain_event_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('domain_event_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('external_connection_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['domain_event_message_id', 'external_connection_id'], 'domain_event_delivery_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_event_deliveries');
        Schema::dropIfExists('domain_event_messages');
        Schema::dropIfExists('external_sync_records');
        Schema::dropIfExists('external_connections');
    }
};
