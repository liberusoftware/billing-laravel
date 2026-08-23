<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TimeEntry;
use App\Services\AuditLogService;

class TimeEntryObserver
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function created(TimeEntry $timeEntry): void
    {
        $this->auditLog->log('time_entry_created', $timeEntry, null, $timeEntry->toArray());
    }

    public function updated(TimeEntry $timeEntry): void
    {
        $this->auditLog->log('time_entry_updated', $timeEntry, $timeEntry->getOriginal(), $timeEntry->getChanges());
    }

    public function deleted(TimeEntry $timeEntry): void
    {
        $this->auditLog->log('time_entry_deleted', $timeEntry, $timeEntry->toArray(), null);
    }
}
