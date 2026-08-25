<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('webhook_endpoints')) {
            return;
        }

        Schema::table('webhook_endpoints', function (Blueprint $table): void {
            if (Schema::hasColumn('webhook_endpoints', 'owner_ref')) {
                $table->string('owner_ref')->nullable()->change();
            }
            if (Schema::hasColumn('webhook_endpoints', 'signing_secret')) {
                $table->text('signing_secret')->nullable()->change();
            }
            if (Schema::hasColumn('webhook_endpoints', 'events')) {
                $table->json('events')->nullable()->change();
            }
            if (! Schema::hasColumn('webhook_endpoints', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable();
            }
            if (! Schema::hasColumn('webhook_endpoints', 'secret')) {
                $table->text('secret')->nullable();
            }
            if (! Schema::hasColumn('webhook_endpoints', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('webhook_endpoints', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('webhook_endpoints', 'max_retries')) {
                $table->unsignedInteger('max_retries')->default(3);
            }
            if (! Schema::hasColumn('webhook_endpoints', 'retry_interval')) {
                $table->unsignedInteger('retry_interval')->default(60);
            }
            if (! Schema::hasColumn('webhook_endpoints', 'last_triggered_at')) {
                $table->timestamp('last_triggered_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // These compatibility columns are retained when rolling back the app
        // migration because the endpoint table itself belongs to the webhooks module.
    }
};
