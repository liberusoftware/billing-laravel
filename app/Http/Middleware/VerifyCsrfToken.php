<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Override;

class VerifyCsrfToken extends PreventRequestForgery
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    #[Override]
    protected $except = [
        //
    ];
}
