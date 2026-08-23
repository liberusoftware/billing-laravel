<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events')->nullable(); // Array of event types to subscribe to
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->integer('max_retries')->default(3);
            $table->integer('retry_interval')->default(60); // seconds
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
