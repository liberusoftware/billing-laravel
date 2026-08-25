<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('git_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('name');
            $table->string('base_url');
            $table->text('access_token');
            $table->text('webhook_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'provider', 'name']);
        });

        Schema::create('git_repositories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('git_connection_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('full_name');
            $table->string('default_branch')->default('main');
            $table->string('web_url');
            $table->boolean('is_private')->default(true);
            $table->timestamp('external_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['git_connection_id', 'external_id']);
        });

        Schema::create('git_sync_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('git_repository_id')->constrained()->cascadeOnDelete();
            $table->string('record_type');
            $table->string('external_id');
            $table->string('title')->nullable();
            $table->string('state')->nullable();
            $table->string('web_url')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('external_created_at')->nullable();
            $table->timestamp('external_updated_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['git_repository_id', 'record_type', 'external_id'], 'git_sync_record_unique');
            $table->index(['git_repository_id', 'record_type']);
        });

        Schema::create('git_releases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('git_repository_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('version');
            $table->string('name');
            $table->text('changelog')->nullable();
            $table->string('state')->default('draft');
            $table->string('web_url')->nullable();
            $table->string('deployment_environment')->nullable();
            $table->string('deployment_status')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();
            $table->unique(['git_repository_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_releases');
        Schema::dropIfExists('git_sync_records');
        Schema::dropIfExists('git_repositories');
        Schema::dropIfExists('git_connections');
    }
};
