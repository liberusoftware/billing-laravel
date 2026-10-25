<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('checkout_key')->nullable();
            $table->string('checkout_fingerprint', 64)->nullable();
            $table->unique(['customer_id', 'checkout_key']);
        });
        Schema::table('tickets', function (Blueprint $table): void {
            $table->timestamp('sla_breached_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn('sla_breached_at');
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['customer_id', 'checkout_key']);
            $table->dropColumn(['checkout_key', 'checkout_fingerprint']);
        });
    }
};
