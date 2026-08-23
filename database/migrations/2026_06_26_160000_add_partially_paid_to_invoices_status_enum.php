<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ponytail: enum ALTER is MySQL-only; on sqlite the column is plain text, so no-op.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY status ENUM('pending','paid','overdue','partially_paid','completed') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY status ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending'");
        }
    }
};
