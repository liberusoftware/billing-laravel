<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        foreach (['plans', 'addons', 'bundles', 'options', 'eligibility', 'channels'] as $type) {
            Schema::create("billing_catalog_{$type}", function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('team_id')->nullable()->index();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->string('status', 32)->default('draft')->index();
                $table->json('configuration')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['plans', 'addons', 'bundles', 'options', 'eligibility', 'channels'] as $type) {
            Schema::dropIfExists("billing_catalog_{$type}");
        }
    }
};
