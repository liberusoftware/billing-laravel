<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', static function (Blueprint $table): void {
            $table->string('format')->nullable()->after('filters');
            $table->json('parameters')->nullable()->after('format');
        });
    }

    public function down(): void
    {
        Schema::table('reports', static function (Blueprint $table): void {
            $table->dropColumn(['format', 'parameters']);
        });
    }
};
