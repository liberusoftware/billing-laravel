<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_contacts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('billing_currencies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->char('code', 3);
            $table->string('name');
            $table->unsignedSmallInteger('decimal_places')->default(2);
            $table->boolean('enabled')->default(true);
            $table->decimal('exchange_rate', 20, 10)->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'code']);
        });
        Schema::create('billing_tax_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('name');
            $table->decimal('rate', 8, 5);
            $table->string('jurisdiction')->nullable();
            $table->boolean('inclusive')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
        Schema::create('billing_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('name');
            $table->string('prefix')->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
            $table->unique(['team_id', 'name']);
        });
        Schema::create('billing_terms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('name');
            $table->unsignedSmallInteger('due_days')->default(0);
            $table->boolean('default')->default(false);
            $table->timestamps();
        });
        Schema::create('billing_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->unique();
            $table->json('values')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['billing_settings', 'billing_terms', 'billing_sequences', 'billing_tax_profiles', 'billing_currencies', 'billing_contacts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
