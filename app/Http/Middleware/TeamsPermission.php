<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class TeamsPermission
{
    public function handle(Request $request, Closure $next)
    {
        if (! empty($user = auth()->user()) && ! empty($user->current_team_id)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->current_team_id);
            auth()->setUser(
                auth()->user()->fresh()
            );
        }

        return $next($request);
    }
}
