<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_provisioned_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->index();
            $table->foreignId('subscription_id')->nullable()->index();
            $table->string('provider');
            $table->string('external_id')->nullable()->index();
            $table->string('state')->index();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_reconciled_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_provisioned_services');
    }
};
