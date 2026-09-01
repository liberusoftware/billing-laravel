<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->text('setup_configuration')->nullable()->after('branding');
            $table->timestamp('setup_completed_at')->nullable()->after('setup_configuration');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->dropColumn(['setup_configuration', 'setup_completed_at']);
        });
    }
};
