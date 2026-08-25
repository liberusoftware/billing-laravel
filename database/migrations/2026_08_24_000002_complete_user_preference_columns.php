<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'locale')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('locale', 5)->default('en');
            });
        }

        if (! Schema::hasColumn('users', 'timezone')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('timezone', 64)->nullable();
            });
        }

        foreach (['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                continue;
            }

            Schema::table('users', function (Blueprint $table) use ($column): void {
                if ($column === 'two_factor_confirmed_at') {
                    $table->timestamp($column)->nullable();
                } else {
                    $table->text($column)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // These compatibility columns may be owned by independently installed
        // modules, so they are intentionally retained on application rollback.
    }
};
