<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $author_id
 * @property string $author_type
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read User $author
 */
#[Fillable([
    'project_id',
    'author_id',
    'author_type',
    'body',
])]
class ProjectMessage extends Model
{
    use HasFactory;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Post a message as the customer who owns the project. Ownership matches the
     * Client panel scoping rule: the project's customer email must equal the
     * user's email (see Client InvoiceResource::getEloquentQuery).
     *
     * @throws AuthorizationException
     */
    public static function postAsCustomer(Project $project, User $user, string $body): self
    {
        if ($project->customer->email !== $user->email) {
            throw new AuthorizationException;
        }

        return $project->messages()->create([
            'author_id' => $user->id,
            'author_type' => 'customer',
            'body' => $body,
        ]);
    }
}
