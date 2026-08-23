<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'currency',
    'default_language',
    'address',
    'country',
    'email',
    'phone_01',
    'phone_02',
    'phone_03',
    'facebook',
    'twitter',
    'github',
    'youtube',
])]
class SiteSettings extends Model {}
