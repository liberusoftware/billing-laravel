<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

use Illuminate\Support\Carbon;
use JoelButcher\Socialstream\ConnectedAccount;
use JoelButcher\Socialstream\Contracts\UpdatesConnectedAccounts;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\One\User as OneUser;
use Laravel\Socialite\Two\User as TwoUser;

class UpdateConnectedAccount implements UpdatesConnectedAccounts
{
    public function update(mixed $user, ConnectedAccount $account, string $provider, ProviderUser $providerUser): ConnectedAccount
    {
        $account->forceFill(
            [
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
        )->save();

        return $account;
    }
}
