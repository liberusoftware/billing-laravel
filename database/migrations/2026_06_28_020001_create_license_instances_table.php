<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_instances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->string('identifier');
            $table->string('ip_address')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->string('local_key')->nullable();
            $table->timestamps();

            $table->unique(['license_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_instances');
    }
};
