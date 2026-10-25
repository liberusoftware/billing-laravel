<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->timestamp('renewal_period_start')->nullable();
            $table->timestamp('renewal_period_end')->nullable();
            $table->index(['subscription_id', 'renewal_period_end']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['subscription_id', 'renewal_period_end']);
            $table->dropColumn(['renewal_period_start', 'renewal_period_end']);
        });
    }
};
