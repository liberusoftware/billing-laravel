<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'access_service_id', 'accounting_session_id', 'started_at', 'ended_at', 'input_bytes', 'output_bytes', 'total_bytes', 'session_seconds', 'nas_identifier', 'ip_address'])]
final class RadiusSession extends Model
{
    protected $table = 'billing_isp_radius_sessions';

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime', 'input_bytes' => 'integer', 'output_bytes' => 'integer', 'total_bytes' => 'integer', 'session_seconds' => 'integer'];
    }
}
