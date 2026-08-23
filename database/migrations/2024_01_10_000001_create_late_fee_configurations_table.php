<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('late_fee_configurations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->enum('fee_type', ['percentage', 'fixed']);
            $table->decimal('fee_amount', 10, 2);
            $table->integer('grace_period_days')->default(0);
            $table->decimal('max_fee_amount', 10, 2)->nullable();
            $table->boolean('is_compound')->default(false);
            $table->enum('frequency', ['one-time', 'daily', 'weekly', 'monthly'])->default('one-time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_fee_configurations');
    }
};
