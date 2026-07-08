<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Project;
use App\Services\AuditLogService;

class ProjectObserver
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function created(Project $project): void
    {
        $this->auditLog->log('project_created', $project, null, $project->toArray());
    }

    public function updated(Project $project): void
    {
        $this->auditLog->log('project_updated', $project, $project->getOriginal(), $project->getChanges());
    }

    public function deleted(Project $project): void
    {
        $this->auditLog->log('project_deleted', $project, $project->toArray(), null);
    }
}
