<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispute_messages', function (Blueprint $table): void {
            $table->json('attachments')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dispute_messages', function (Blueprint $table): void {
            $table->dropColumn('attachments');
        });
    }
};
