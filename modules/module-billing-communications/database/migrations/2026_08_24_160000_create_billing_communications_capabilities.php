<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_communication_numbers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->foreignId('service_id')->nullable()->index();
            $table->string('number');
            $table->string('type')->default('phone');
            $table->string('status')->default('available')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'number']);
        });
        Schema::create('billing_communication_usage_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('provider');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('rows')->default(0);
            $table->unsignedBigInteger('total_amount_minor')->default(0);
            $table->char('currency', 3)->default('USD');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('billing_communication_providers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('name');
            $table->string('driver');
            $table->string('status')->default('active')->index();
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_communication_providers');
        Schema::dropIfExists('billing_communication_usage_imports');
        Schema::dropIfExists('billing_communication_numbers');
    }
};
