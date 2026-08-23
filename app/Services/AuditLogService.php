<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function log(string $event, ?Model $model = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::create(
            [
                'user_id' => Auth::id(),
                'event' => $event,
                'auditable_type' => $model instanceof Model ? $model::class : null,
                'auditable_id' => $model?->getKey(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]
        );
    }
}
