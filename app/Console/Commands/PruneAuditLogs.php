<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Prune old audit logs')]
#[Signature('audit:prune {--days=90}')]
class PruneAuditLogs extends Command
{
    public function handle(): void
    {
        $days = (int) $this->option('days');
        $count = AuditLog::where(
            'created_at',
            '<',
            now()->subDays($days)
        )->delete();

        $this->info("Deleted {$count} audit logs older than {$days} days.");
    }
}
