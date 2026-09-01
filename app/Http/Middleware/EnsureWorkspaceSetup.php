<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWorkspaceSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $team = $user?->currentTeam;
        $team = $team instanceof Team ? $team : null;

        $setupPath = trim($request->path(), '/') === 'app/account-setup';
        $isOwnedTeam = $user !== null
            && $team !== null
            && (int) ($team->getAttributes()['user_id'] ?? 0) === (int) $user->getKey();

        if ($isOwnedTeam && ! $setupPath && $team->setup_completed_at === null) {
            return redirect('/app/account-setup');
        }

        $configuration = $team !== null ? ($team->setup_configuration ?? []) : [];
        $overrides = array_filter([
            'services.stripe.secret' => $configuration['stripe_secret'] ?? null,
            'services.paddle.token' => $configuration['paddle_token'] ?? null,
            'services.tax_api.api_key' => $configuration['tax_api_key'] ?? null,
            'services.resellerclub.api_key' => $configuration['resellerclub_api_key'] ?? null,
            'services.github.client_id' => $configuration['github_client_id'] ?? null,
            'services.github.client_secret' => $configuration['github_client_secret'] ?? null,
            'services.google.client_id' => $configuration['google_client_id'] ?? null,
            'services.google.client_secret' => $configuration['google_client_secret'] ?? null,
        ], static fn (mixed $value): bool => is_string($value) && $value !== '');

        $original = [];
        foreach ($overrides as $key => $value) {
            $original[$key] = config($key);
            config([$key => $value]);
        }

        try {
            return $next($request);
        } finally {
            foreach ($original as $key => $value) {
                config([$key => $value]);
            }
        }
    }
}
