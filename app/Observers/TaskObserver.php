<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Task;
use App\Services\AuditLogService;

class TaskObserver
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function created(Task $task): void
    {
        $this->auditLog->log('task_created', $task, null, $task->toArray());
    }

    public function updated(Task $task): void
    {
        $this->auditLog->log('task_updated', $task, $task->getOriginal(), $task->getChanges());
    }

    public function deleted(Task $task): void
    {
        $this->auditLog->log('task_deleted', $task, $task->toArray(), null);
    }
}
