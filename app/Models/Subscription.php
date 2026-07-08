<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $subscription_plan_id
 * @property int|null $product_service_id
 * @property int|null $team_id
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string $renewal_period
 * @property string $status
 * @property string $price
 * @property string $currency
 * @property bool $auto_renew
 * @property Carbon|null $last_billed_at
 * @property Carbon|null $ends_at
 * @property string|null $domain
 * @property string|null $domain_name
 * @property string|null $domain_registrar
 * @property Carbon|null $domain_expiration_date
 * @property string|null $domain_transfer_status
 * @property bool $id_protection
 * @property array|null $scheduled_change
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Customer|null $customer
 * @property-read SubscriptionPlan|null $subscriptionPlan
 * @property-read Products_Service|null $productService
 * @property-read Collection<int, Invoice> $invoices
 * @property-read Collection<int, ServiceSuspension> $suspensions
 * @property-read ServiceSuspension|null $activeSuspension
 * @property-read HostingAccount|null $hostingAccount
 */
#[Fillable([
    'customer_id',
    'subscription_plan_id',
    'product_service_id',
    'start_date',
    'end_date',
    'renewal_period',
    'status',
    'price',
    'currency',
    'auto_renew',
    'last_billed_at',
    'ends_at',
    'domain',
    'domain_name',
    'domain_registrar',
    'domain_expiration_date',
    'domain_transfer_status',
    'id_protection',
    'scheduled_change',
])]
class Subscription extends Model
{
    use HasFactory;

    #[Override]
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_billed_at' => 'datetime',
    ];

    #[Override]
    protected function casts(): array
    {

        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'domain_expiration_date' => 'datetime',
            'scheduled_change' => 'array',
            'price' => 'decimal:2',
            'currency' => 'string',
            'auto_renew' => 'boolean',
            'last_billed_at' => 'datetime',
            'ends_at' => 'datetime',
            'id_protection' => 'boolean',
        ];

    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function productService(): BelongsTo
    {
        return $this->belongsTo(
            Products_Service::class,
            'product_service_id'
        );
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function suspensions(): HasMany
    {
        return $this->hasMany(ServiceSuspension::class);
    }

    public function hostingAccount(): HasOne
    {
        return $this->hasOne(HostingAccount::class);
    }

    public function activeSuspension(): HasOne
    {
        return $this->hasOne(ServiceSuspension::class)
            ->where(
                'is_active',
                true
            )
            ->whereNull('unsuspended_at');
    }

    public function renew(): bool
    {
        if (! $this->auto_renew || $this->status === 'cancelled') {
            return false;
        }

        $renewalPeriod = $this->getRenewalPeriod();
        $this->end_date = Carbon::parse($this->end_date)->add($renewalPeriod);
        $this->last_billed_at = now();
        $this->status = 'active';

        return $this->save();
    }

    public function cancel(): bool
    {
        $this->auto_renew = false;
        $this->status = 'cancelled';

        return $this->save();
    }

    public function suspend(): bool
    {
        $this->status = 'suspended';

        return $this->save();
    }

    public function resume(): bool
    {
        if ($this->status === 'suspended') {
            $this->status = 'active';

            return $this->save();
        }

        return false;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function needsBilling(): bool
    {
        if (! $this->last_billed_at) {
            return true;
        }

        $nextBillingDate = Carbon::parse($this->last_billed_at)->add($this->getRenewalPeriod());

        return $nextBillingDate->isPast() && $this->isActive();
    }

    private function getRenewalPeriod(): string
    {
        return match ($this->renewal_period) {
            'monthly' => '1 month',
            'quarterly' => '3 months',
            'semi-annually' => '6 months',
            'annually' => '1 year',
            default => '1 month',
        };
    }
}
