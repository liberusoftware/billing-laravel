<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('customer_type')->default('individual')->after('team_id');
            $table->string('lifecycle_status')->default('prospect')->after('customer_type');
            $table->json('tags')->nullable()->after('country');
            $table->json('custom_fields')->nullable()->after('tags');
            $table->timestamp('status_changed_at')->nullable()->after('custom_fields');
            $table->index(['team_id', 'customer_type']);
            $table->index(['team_id', 'lifecycle_status']);
        });

        Schema::table('client_contacts', function (Blueprint $table): void {
            $table->string('contact_type')->default('administrative')->after('title');
            $table->index(['customer_id', 'contact_type']);
        });
    }

    public function down(): void
    {
        Schema::table('client_contacts', function (Blueprint $table): void {
            $table->dropIndex(['customer_id', 'contact_type']);
            $table->dropColumn('contact_type');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['team_id', 'customer_type']);
            $table->dropIndex(['team_id', 'lifecycle_status']);
            $table->dropColumn([
                'customer_type',
                'lifecycle_status',
                'tags',
                'custom_fields',
                'status_changed_at',
            ]);
        });
    }
};
