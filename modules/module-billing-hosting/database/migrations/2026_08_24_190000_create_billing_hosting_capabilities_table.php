<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_hosting_capabilities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('hosting_account_id')->nullable()->index();
            $table->string('type')->index();
            $table->string('name');
            $table->string('status')->default('pending')->index();
            $table->string('provider')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_hosting_capabilities');
    }
};
