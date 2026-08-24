<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('radius_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('isp_service_id')->constrained()->cascadeOnDelete();
            $table->string('accounting_session_id');
            $table->string('nas_identifier')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('input_bytes')->default(0);
            $table->unsignedBigInteger('output_bytes')->default(0);
            $table->unsignedInteger('session_seconds')->default(0);
            $table->timestamps();

            $table->unique(['isp_service_id', 'accounting_session_id']);
            $table->index(['isp_service_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radius_sessions');
    }
};
