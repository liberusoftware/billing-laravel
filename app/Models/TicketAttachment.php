<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property int|null $uploaded_by
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model $attachable
 * @property-read User|null $uploader
 */
#[Fillable([
    'attachable_type',
    'attachable_id',
    'uploaded_by',
    'path',
    'original_name',
    'mime',
    'size',
])]
class TicketAttachment extends Model
{
    use HasFactory;

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Resolve the owning ticket regardless of whether the attachment hangs off
     * the ticket itself or one of its responses.
     */
    public function owningTicket(): Ticket
    {
        $attachable = $this->attachable;

        return $attachable instanceof Ticket ? $attachable : $attachable->ticket;
    }
}
