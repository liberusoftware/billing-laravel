<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone_number
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property CustomerType $customer_type
 * @property CustomerStatus $lifecycle_status
 * @property array<int, string>|null $tags
 * @property array<string, mixed>|null $custom_fields
 * @property Carbon|null $status_changed_at
 * @property bool $sms_notifications_enabled
 * @property int|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Credit> $credits
 * @property-read Collection<int, ClientContact> $contacts
 * @property-read Collection<int, Quote> $quotes
 * @property-read Collection<int, Invoice> $invoices
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read User|null $user
 * @property-read PaymentMethod|null $defaultPaymentMethod
 */
#[Fillable([
    'name',
    'email',
    'phone_number',
    'address',
    'city',
    'state',
    'postal_code',
    'country',
    'customer_type',
    'lifecycle_status',
    'tags',
    'custom_fields',
    'status_changed_at',
])]
class Customer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'lifecycle_status' => CustomerStatus::class,
            'tags' => 'array',
            'custom_fields' => 'array',
            'status_changed_at' => 'datetime',
        ];
    }

    public function transitionTo(CustomerStatus $status): bool
    {
        if ($this->lifecycle_status === $status) {
            return true;
        }

        $this->lifecycle_status = $status;
        $this->status_changed_at = now();

        return $this->save();
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultPaymentMethod(): HasOne
    {
        return $this->hasOne(PaymentMethod::class)->where('is_default', true);
    }
}
