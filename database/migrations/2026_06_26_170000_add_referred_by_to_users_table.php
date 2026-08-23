<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Affiliate::referrals() = hasMany(User, 'referred_by'); the column
            // was never created, so the relationship (and AffiliateReportingService)
            // threw "no column referred_by".
            $table->foreignId('referred_by')
                ->nullable()
                ->after('id')
                ->constrained('affiliates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('referred_by');
        });
    }
};
