<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\DomainService;
use App\Services\Registrars\EnomClient;
use App\Services\Registrars\ResellerClubClient;
use Exception;
use Tests\TestCase;

class RegistrarResolutionTest extends TestCase
{
    public function test_domain_service_resolves_registrar_by_name(): void
    {
        $service = app(DomainService::class);

        $this->assertInstanceOf(EnomClient::class, $service->clientFor('enom'));
        $this->assertInstanceOf(ResellerClubClient::class, $service->clientFor('resellerclub'));

        $this->expectException(Exception::class);
        $service->clientFor('does-not-exist');
    }
}
