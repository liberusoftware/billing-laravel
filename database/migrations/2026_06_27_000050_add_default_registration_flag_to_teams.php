<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The team new registrants are attached to is admin-controlled via this flag
     * (exactly one team carries it). Replaces the previous Team::first() guess.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->boolean('is_default_for_registration')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->dropColumn('is_default_for_registration');
        });
    }
};
