<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('billing_tax_profiles', function (Blueprint $table): void {
            $table->decimal('threshold_amount', 20, 6)->nullable();
            $table->decimal('threshold_rate', 8, 5)->nullable();
        });

        Schema::create('billing_tax_exemptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('enabled')->default(true);
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_tax_exemptions');
        Schema::table('billing_tax_profiles', function (Blueprint $table): void {
            $table->dropColumn(['threshold_amount', 'threshold_rate']);
        });
    }
};
