<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_service_id')->nullable()->constrained('products_services')->nullOnDelete();
            $table->string('technology');
            $table->string('status')->default('pending');
            $table->string('radius_platform');
            $table->string('radius_username');
            $table->text('radius_secret');
            $table->unsignedBigInteger('download_limit_bps')->nullable();
            $table->unsignedBigInteger('upload_limit_bps')->nullable();
            $table->unsignedBigInteger('monthly_data_limit_bytes')->nullable();
            $table->unsignedBigInteger('current_period_usage_bytes')->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->timestamp('radius_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'radius_username']);
            $table->index(['team_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_services');
    }
};
