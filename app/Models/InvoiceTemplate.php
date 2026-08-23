<?php

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string $company_name
 * @property string|null $company_address
 * @property string|null $company_phone
 * @property string|null $company_email
 * @property string|null $logo_path
 * @property string|null $header_text
 * @property string|null $footer_text
 * @property string $color_scheme
 * @property bool $is_default
 * @property int|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $logo_url
 * @property-read string $styled_html
 * @property-read Collection<int, Invoice> $invoices
 */
#[Fillable([
    'name',
    'company_name',
    'company_address',
    'company_phone',
    'company_email',
    'logo_path',
    'header_text',
    'footer_text',
    'color_scheme',
    'is_default',
    'team_id',
])]
class InvoiceTemplate extends Model
{
    use HasTeam;

    #[Override]
    protected function casts(): array
    {

        return [
            'is_default' => 'boolean',
        ];

    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public static function getDefault()
    {
        return static::where(
            'team_id',
            auth()->user()?->current_team_id
        )
            ->where(
                'is_default',
                true
            )
            ->first();
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null);
    }

    protected function styledHtml(): Attribute
    {
        return Attribute::make(
            get: fn (): string => sprintf(
                '<style>
                .invoice-box { color: %1$s; }
                .invoice-header { border-color: %1$s; }
                .invoice-items th { background-color: %1$s; color: white; }
                .invoice-items td { border-color: %1$s; }
            </style>',
                $this->color_scheme
            )
        );
    }
}
