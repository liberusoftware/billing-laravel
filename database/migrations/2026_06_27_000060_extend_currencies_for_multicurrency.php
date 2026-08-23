<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            // exchange_rate already exists: rate relative to the base currency (base = 1).
            $table->boolean('is_enabled')->default(true)->after('exchange_rate');
            $table->boolean('is_base')->default(false)->after('is_enabled');
            $table->string('symbol')->nullable()->after('name');
            $table->unsignedTinyInteger('decimal_precision')->default(2)->after('is_base');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            $table->dropColumn(['is_enabled', 'is_base', 'symbol', 'decimal_precision']);
        });
    }
};
