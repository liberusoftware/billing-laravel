<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('billing_order_quotes', function (Blueprint $table): void {
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expired_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('billing_order_quotes', function (Blueprint $table): void {
            $table->dropColumn(['sent_at', 'viewed_at', 'accepted_at', 'declined_at', 'expired_at']);
        });
    }
};
