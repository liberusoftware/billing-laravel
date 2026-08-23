<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

use JoelButcher\Socialstream\Contracts\ResolvesSocialiteUsers;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Laravel\Socialite\Contracts\User;

class ResolveSocialiteUser implements ResolvesSocialiteUsers
{
    public function resolve(string $provider): User
    {
        return app(Socialite::class)->driver($provider)->user();
    }
}
