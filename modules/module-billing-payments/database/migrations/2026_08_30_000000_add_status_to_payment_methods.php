<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('billing_payment_methods', 'status')) {
            Schema::table('billing_payment_methods', function (Blueprint $table): void {
                $table->string('status')->default('active')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('billing_payment_methods', 'status')) {
            Schema::table('billing_payment_methods', function (Blueprint $table): void {
                $table->dropColumn('status');
            });
        }
    }
};
