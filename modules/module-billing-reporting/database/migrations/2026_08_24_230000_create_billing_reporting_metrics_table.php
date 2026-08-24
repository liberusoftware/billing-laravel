<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_reporting_metrics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->string('metric')->index();
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->decimal('value', 20, 6);
            $table->char('currency', 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'metric', 'period_start', 'period_end', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_reporting_metrics');
    }
};
