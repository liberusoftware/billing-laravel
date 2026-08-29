<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('billing_subscriptions', function (Blueprint $table): void {
            $table->unsignedInteger('period_days')->default(30)->after('current_period_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_subscriptions', function (Blueprint $table): void {
            $table->dropColumn('period_days');
        });
    }
};
