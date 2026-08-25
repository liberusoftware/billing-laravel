<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->foreignId('parent_team_id')->nullable()->after('id')->constrained('teams')->nullOnDelete();
            $table->string('organisation_type')->default('company')->after('name');
            $table->string('slug')->nullable()->unique()->after('organisation_type');
            $table->string('custom_domain')->nullable()->unique()->after('slug');
            $table->string('database_mode')->default('shared')->after('custom_domain');
            $table->json('branding')->nullable()->after('database_mode');
            $table->timestamp('archived_at')->nullable()->after('branding');
            $table->index(['parent_team_id', 'organisation_type']);
        });

        Schema::create('reseller_agreements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('reseller_team_id')->constrained('teams')->cascadeOnDelete();
            $table->decimal('default_discount_percent', 5, 2)->default(0);
            $table->decimal('revenue_share_percent', 5, 2)->default(0);
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->decimal('credit_used', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('active');
            $table->json('product_pricing')->nullable();
            $table->timestamps();
            $table->unique(['provider_team_id', 'reseller_team_id']);
        });

        Schema::create('organisation_brands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_primary')->default(false);
            $table->json('theme')->nullable();
            $table->json('email_branding')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'slug']);
        });

        Schema::create('brand_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organisation_brand_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reseller_service_delegations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->decimal('wholesale_price', 12, 2);
            $table->decimal('retail_price', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique('subscription_id');
        });

        Schema::create('reseller_revenue_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reseller_service_delegation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('provider_amount', 12, 2);
            $table->decimal('reseller_amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status')->default('pending');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->index(['reseller_agreement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_revenue_transactions');
        Schema::dropIfExists('reseller_service_delegations');
        Schema::dropIfExists('brand_domains');
        Schema::dropIfExists('organisation_brands');
        Schema::dropIfExists('reseller_agreements');

        Schema::table('teams', function (Blueprint $table): void {
            $table->dropForeign(['parent_team_id']);
            $table->dropIndex(['parent_team_id', 'organisation_type']);
            $table->dropUnique(['slug']);
            $table->dropUnique(['custom_domain']);
            $table->dropColumn([
                'parent_team_id', 'organisation_type', 'slug', 'custom_domain',
                'database_mode', 'branding', 'archived_at',
            ]);
        });
    }
};
