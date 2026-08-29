<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('billing_isp_records', function (Blueprint $table): void {
            $table->unsignedBigInteger('monthly_data_limit_bytes')->nullable();
            $table->unsignedBigInteger('current_period_usage_bytes')->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('radius_synced_at')->nullable();
            $table->string('suspension_reason')->nullable();
        });

        Schema::create('billing_isp_radius_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('access_service_id')->index();
            $table->string('accounting_session_id');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('input_bytes')->default(0);
            $table->unsignedBigInteger('output_bytes')->default(0);
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->unsignedInteger('session_seconds')->nullable();
            $table->string('nas_identifier')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->unique(['access_service_id', 'accounting_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_isp_radius_sessions');
        Schema::table('billing_isp_records', function (Blueprint $table): void {
            $table->dropColumn(['monthly_data_limit_bytes', 'current_period_usage_bytes', 'activated_at', 'suspended_at', 'radius_synced_at', 'suspension_reason']);
        });
    }
};
