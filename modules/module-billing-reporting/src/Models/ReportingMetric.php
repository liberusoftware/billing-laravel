<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Models;

use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Reporting\Enums\ReportingMetricType;

final class ReportingMetric extends Model
{
    protected $table = 'billing_reporting_metrics';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['metric' => ReportingMetricType::class, 'value' => 'decimal:6', 'period_start' => 'date', 'period_end' => 'date', 'dimensions' => 'array'];
    }
}
