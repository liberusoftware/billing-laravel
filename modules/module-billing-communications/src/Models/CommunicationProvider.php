<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Models;

use Illuminate\Database\Eloquent\Model;

final class CommunicationProvider extends Model
{
    protected $table = 'billing_communication_providers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }
}
