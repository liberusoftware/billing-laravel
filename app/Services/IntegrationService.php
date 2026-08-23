<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\User;

class IntegrationService
{
    protected array $providers = [
        'google' => [
            'name' => 'Google Calendar',
            'icon' => 'google',
            'scopes' => [
                'calendar',
                'calendar.events',
            ],
        ],
        'slack' => [
            'name' => 'Slack',
            'icon' => 'slack',
            'scopes' => [
                'channels:read',
                'chat:write',
            ],
        ],
        'trello' => [
            'name' => 'Trello',
            'icon' => 'trello',
            'scopes' => [
                'read',
                'write',
            ],
        ],
    ];

    public function connect(string $provider, User $user, array $data)
    {
        return Integration::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'token' => $data['token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => isset($data['expires_in']) ? now()->addSeconds($data['expires_in']) : null,
                'scopes' => $data['scopes'] ?? [],
                'settings' => $data['settings'] ?? [],
            ]
        );
    }

    public function disconnect(string $provider, User $user)
    {
        return $user->integrations()->where(
            'provider',
            $provider
        )->delete();
    }

    public function getProviders(): array
    {
        return $this->providers;
    }
}
