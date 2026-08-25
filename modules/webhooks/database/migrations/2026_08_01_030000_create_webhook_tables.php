<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_ref')->nullable()->index();
            $table->foreignId('team_id')->nullable();
            $table->text('url');
            $table->text('signing_secret')->nullable();
            $table->text('secret')->nullable();
            $table->json('events')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedInteger('max_retries')->default(3);
            $table->unsignedInteger('retry_interval')->default(60);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->uuid('event_id')->index();
            $table->string('event');
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['endpoint_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
    }
};
