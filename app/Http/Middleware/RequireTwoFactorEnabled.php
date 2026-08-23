<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->two_factor_secret) {
            return redirect()->route('profile.show')
                ->with(
                    'error',
                    'Two-factor authentication must be enabled to access this area.'
                );
        }

        return $next($request);
    }
}
