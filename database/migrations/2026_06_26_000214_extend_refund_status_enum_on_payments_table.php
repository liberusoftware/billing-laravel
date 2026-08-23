<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // sqlite stores enums as plain text (no constraint), so only mysql needs altering.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY refund_status ENUM('none','pending','partial','full','completed') NOT NULL DEFAULT 'none'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY refund_status ENUM('none','pending','completed') NOT NULL DEFAULT 'none'");
        }
    }
};
