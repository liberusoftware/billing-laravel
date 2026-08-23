<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subscriptions were wired to product_service_id (usage model) only. Flat-rate
     * plan subscriptions have no product service, so allow either link: add a
     * nullable subscription_plan_id and relax product_service_id to nullable.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('subscriptions', 'subscription_plan_id')) {
                $table->foreignId('subscription_plan_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('subscription_plans')
                    ->nullOnDelete();
            }

            $table->foreignId('product_service_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('subscriptions', 'subscription_plan_id')) {
                $table->dropConstrainedForeignId('subscription_plan_id');
            }
        });
    }
};
