<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'voip_account_id', 'number', 'country_code', 'monthly_price', 'currency', 'status'])]
class DidNumber extends Model
{
    use HasTeam;

    protected function casts(): array
    {
        return ['monthly_price' => 'decimal:2'];
    }

    public function voipAccount(): BelongsTo
    {
        return $this->belongsTo(VoipAccount::class);
    }
}
