<?php

declare(strict_types=1);

namespace Tests\Feature;

use JoelButcher\Socialstream\Providers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SocialstreamTest extends TestCase
{
    #[Test]
    #[DataProvider('socialMediaProviders')]
    public function test_socialstream_config_has_social_media_providers(string $provider): void
    {
        $configuredProviders = config('socialstream.providers');

        $this->assertContains(
            $provider,
            $configuredProviders,
            "Provider [{$provider}] is not configured in socialstream config."
        );
    }

    public static function socialMediaProviders(): array
    {
        return [
            'bitbucket' => [Providers::bitbucket()],
            'facebook' => [Providers::facebook()],
            'github' => [Providers::github()],
            'gitlab' => [Providers::gitlab()],
            'google' => [Providers::google()],
            'linkedin' => [Providers::linkedin()],
            'linkedinOpenId' => [Providers::linkedinOpenId()],
            'slack' => [Providers::slack()],
            'twitter-oauth-2' => [Providers::twitterOAuth2()],
            // twitter-oauth-1 excluded: OAuth 1.0 requires live API keys even for redirect
        ];
    }
}
