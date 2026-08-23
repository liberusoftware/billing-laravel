<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTeam
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
