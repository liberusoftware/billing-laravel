<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('payment_method')->change();
            $table->string('transaction_id')->nullable()->change();
            $table->json('payment_method_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('payment_method_details');
        });
        // Keep the widened method and nullable reference so existing pending payments remain valid.
    }
};
