<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $version
 * @property string|null $description
 * @property bool $enabled
 * @property array|null $dependencies
 * @property array|null $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Module extends Model
{
    protected $fillable = [
        'name',
        'version',
        'description',
        'enabled',
        'dependencies',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'dependencies' => 'array',
            'config' => 'array',
        ];
    }

    public static function findByName(string $name): ?self
    {
        return static::where(
            'name',
            $name
        )->first();
    }
}
