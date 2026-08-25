<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_domain_contacts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('handle');
            $table->string('name');
            $table->string('email');
            $table->json('details')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'handle']);
        });
        Schema::create('billing_domain_dns_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->foreignId('domain_id')->index();
            $table->string('type');
            $table->string('host');
            $table->text('value');
            $table->unsignedInteger('ttl')->default(3600);
            $table->timestamps();
        });
        Schema::create('billing_domain_epp_operations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->foreignId('domain_id')->index();
            $table->string('operation');
            $table->string('status')->default('pending')->index();
            $table->string('epp_code')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_domain_epp_operations');
        Schema::dropIfExists('billing_domain_dns_records');
        Schema::dropIfExists('billing_domain_contacts');
    }
};
