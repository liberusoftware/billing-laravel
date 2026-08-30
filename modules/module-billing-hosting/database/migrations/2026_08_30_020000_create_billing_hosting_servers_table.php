<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_hosting_servers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('name');
            $table->string('hostname');
            $table->string('username')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('control_panel')->index();
            $table->string('api_url')->nullable();
            $table->text('api_token')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('max_accounts')->nullable();
            $table->unsignedInteger('active_accounts')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['team_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_hosting_servers');
    }
};
