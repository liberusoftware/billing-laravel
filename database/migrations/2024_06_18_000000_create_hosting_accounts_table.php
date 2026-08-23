<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('hosting_server_id')->nullable()->constrained()->onDelete('set null');
            $table->string('control_panel')->nullable();
            $table->string('username');
            $table->string('domain');
            $table->string('package');
            $table->string('status');
            $table->decimal('price', 10, 2)->nullable();
            $table->json('addons')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_accounts');
    }
};
