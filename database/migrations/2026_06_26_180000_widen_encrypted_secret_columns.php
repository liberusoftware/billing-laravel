<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The api_key/secret_key/token/secret columns are now cast 'encrypted'.
     * Laravel's encrypted ciphertext is far longer than the plaintext and
     * overflows VARCHAR(255) on MySQL (silent truncation -> unrecoverable
     * secrets). Widen them to TEXT. (sqlite has no length limit, so this is
     * a no-op there but keeps the schema consistent.)
     */
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table): void {
            $table->text('api_key')->change();
            $table->text('secret_key')->change();
        });

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->text('token')->nullable()->change();
        });

        Schema::table('webhook_endpoints', function (Blueprint $table): void {
            $table->text('secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table): void {
            $table->string('api_key')->change();
            $table->string('secret_key')->change();
        });

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->string('token')->nullable()->change();
        });

        Schema::table('webhook_endpoints', function (Blueprint $table): void {
            $table->string('secret')->nullable()->change();
        });
    }
};
