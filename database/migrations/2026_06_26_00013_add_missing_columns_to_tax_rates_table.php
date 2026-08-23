<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_rates', static function (Blueprint $table): void {
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('threshold_amount', 10, 2)->nullable();
            $table->decimal('threshold_rate', 5, 2)->nullable();
            $table->string('tax_category')->nullable();
            $table->string('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tax_rates', static function (Blueprint $table): void {
            $table->dropColumn(['effective_date', 'expiry_date', 'threshold_amount', 'threshold_rate', 'tax_category', 'description']);
        });
    }
};
