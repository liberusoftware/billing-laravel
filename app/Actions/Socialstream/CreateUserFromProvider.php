<?php

declare(strict_types=1);

namespace App\Actions\Socialstream;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use JoelButcher\Socialstream\Contracts\CreatesConnectedAccounts;
use JoelButcher\Socialstream\Contracts\CreatesUserFromProvider;
use JoelButcher\Socialstream\Socialstream;
use Laravel\Socialite\Contracts\User as ProviderUser;

class CreateUserFromProvider implements CreatesUserFromProvider
{
    public function __construct(
        private readonly CreatesConnectedAccounts $createsConnectedAccounts
    ) {}

    public function create(string $provider, ProviderUser $providerUser): User
    {
        return DB::transaction(
            fn () => tap(
                User::create(
                    [
                        'name' => $providerUser->getName() ?? $providerUser->getNickname(),
                        'email' => $providerUser->getEmail(),
                    ]
                ),
                function (User $user) use ($provider, $providerUser): void {
                    $user->markEmailAsVerified();

                    if (Socialstream::hasProviderAvatarsFeature() && $providerUser->getAvatar()) {
                        $user->setProfilePhotoFromUrl($providerUser->getAvatar());
                    }

                    $this->createsConnectedAccounts->create(
                        $user,
                        $provider,
                        $providerUser
                    );
                }
            )
        );
    }
}
