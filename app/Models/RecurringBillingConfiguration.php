<?php

namespace App\Models;

use App\Traits\HasTeam;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int|null $invoice_id
 * @property string $frequency
 * @property int|null $billing_day
 * @property Carbon $next_billing_date
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice|null $invoice
 */
#[Fillable([
    'invoice_id',
    'frequency',
    'billing_day',
    'next_billing_date',
    'is_active',
])]
class RecurringBillingConfiguration extends Model
{
    use HasTeam;

    #[Override]
    protected function casts(): array
    {
        return [
            'next_billing_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function calculateNextBillingDate(): CarbonInterface
    {
        $date = now();

        if ($this->billing_day && $this->billing_day > $date->day) {
            $date->setDay($this->billing_day);
        } else {
            $date = match ($this->frequency) {
                'monthly' => $date->addMonth(),
                'quarterly' => $date->addMonths(3),
                'yearly' => $date->addYear(),
                default => $date->addMonth()
            };

            if ($this->billing_day) {
                $date->setDay($this->billing_day);
            }
        }

        return $date;
    }
}
