<?php

use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable, no backfill: clients has no link to any team-owned entity
        // (no customer_id/user_id), so there is no sensible team to infer.
        // A wrong backfill would leak data across tenants; null is safer.
        Schema::table('clients', function (Blueprint $table): void {
            $table->foreignId('team_id')->nullable()->after('id')->constrained('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropForeignIdFor(Team::class);
            $table->dropColumn('team_id');
        });
    }
};
