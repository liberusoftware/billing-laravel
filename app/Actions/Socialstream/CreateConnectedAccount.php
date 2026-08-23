<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use JoelButcher\Socialstream\ConnectedAccount as SocialstreamConnectedAccount;
use JoelButcher\Socialstream\Contracts\CreatesConnectedAccounts;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\One\User as OneUser;
use Laravel\Socialite\Two\User as TwoUser;

class CreateConnectedAccount implements CreatesConnectedAccounts
{
    public function create(Authenticatable $user, string $provider, ProviderUser $providerUser): SocialstreamConnectedAccount
    {
        assert($user instanceof User);

        /** @var SocialstreamConnectedAccount $account */
        $account = $user->connectedAccounts()->create(
            [
                'provider' => $provider,
                'provider_id' => $providerUser->getId(),
                'name' => $providerUser->getName(),
                'nickname' => $providerUser->getNickname(),
                'email' => $providerUser->getEmail(),
                'avatar_path' => $providerUser->getAvatar(),
                'token' => $providerUser instanceof TwoUser ? $providerUser->token : '',
                'secret' => $providerUser instanceof OneUser ? $providerUser->tokenSecret : null,
                'refresh_token' => $providerUser instanceof TwoUser ? $providerUser->refreshToken : null,
                'expires_at' => $providerUser instanceof TwoUser && $providerUser->expiresIn !== null
                    ? Carbon::now()->addSeconds($providerUser->expiresIn)
                    : null,
            ]
        );

        return $account;
    }
}
