<?php

namespace App\Models;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $title
 * @property ContactType $contact_type
 * @property bool $is_primary
 * @property bool $can_view_invoices
 * @property bool $can_make_payments
 * @property bool $can_manage_services
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Customer|null $customer
 * @property-read string $full_name
 */
#[Fillable([
    'customer_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'title',
    'contact_type',
    'is_primary',
    'can_view_invoices',
    'can_make_payments',
    'can_manage_services',
])]
class ClientContact extends Model
{
    #[Override]
    protected function casts(): array
    {

        return [
            'is_primary' => 'boolean',
            'can_view_invoices' => 'boolean',
            'can_make_payments' => 'boolean',
            'can_manage_services' => 'boolean',
            'contact_type' => ContactType::class,
        ];

    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(get: fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }
}
