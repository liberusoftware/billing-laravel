<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infrastructure_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('infrastructure_assets')->nullOnDelete();
            $table->string('asset_type');
            $table->string('name');
            $table->string('hostname')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('vendor')->nullable();
            $table->string('model')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'serial_number']);
            $table->index(['team_id', 'asset_type', 'status']);
        });

        Schema::create('ip_pools', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('infrastructure_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('cidr');
            $table->unsignedTinyInteger('address_family');
            $table->string('first_address', 45);
            $table->string('last_address', 45);
            $table->string('next_address', 45)->nullable();
            $table->string('gateway', 45)->nullable();
            $table->unsignedInteger('vlan_id')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'cidr']);
            $table->index(['team_id', 'address_family']);
        });

        Schema::create('ip_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ip_pool_id')->constrained()->cascadeOnDelete();
            $table->string('address', 45);
            $table->string('status')->default('assigned');
            $table->nullableMorphs('assignable');
            $table->string('hostname')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'address']);
            $table->index(['ip_pool_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_addresses');
        Schema::dropIfExists('ip_pools');
        Schema::dropIfExists('infrastructure_assets');
    }
};
