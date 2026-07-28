<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\InfrastructureAsset;
use App\Models\Team;
use App\Services\IpamService;
use App\Support\IpNetwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class IpamServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cidr_parser_calculates_ipv4_and_ipv6_usable_ranges(): void
    {
        $ipv4 = IpNetwork::parse('192.0.2.19/29');
        $this->assertSame('192.0.2.16/29', $ipv4['canonical']);
        $this->assertSame('192.0.2.17', $ipv4['first']);
        $this->assertSame('192.0.2.22', $ipv4['last']);

        $ipv6 = IpNetwork::parse('2001:db8::123/126');
        $this->assertSame('2001:db8::120/126', $ipv6['canonical']);
        $this->assertSame('2001:db8::121', $ipv6['first']);
        $this->assertSame('2001:db8::123', $ipv6['last']);
    }

    public function test_allocator_tracks_assignments_exhaustion_release_and_reuse(): void
    {
        $asset = InfrastructureAsset::factory()->create();
        $pool = app(IpamService::class)->createPool($asset->team_id, [
            'name' => 'Point to point',
            'cidr' => '198.51.100.0/31',
            'infrastructure_asset_id' => $asset->id,
        ]);

        $first = app(IpamService::class)->allocate($pool, $asset, 'router-a.example.test');
        $second = app(IpamService::class)->allocate($pool, $asset);
        $this->assertSame('198.51.100.0', $first->address);
        $this->assertSame('198.51.100.1', $second->address);
        $this->assertSame($asset->getMorphClass(), $first->assignable_type);
        $this->assertSame($asset->id, $first->assignable_id);

        $this->expectException(RuntimeException::class);
        app(IpamService::class)->allocate($pool, $asset);
    }

    public function test_released_address_is_reused_before_pool_is_exhausted(): void
    {
        $asset = InfrastructureAsset::factory()->create();
        $pool = app(IpamService::class)->createPool($asset->team_id, [
            'name' => 'Single',
            'cidr' => '203.0.113.9/32',
        ]);
        $address = app(IpamService::class)->allocate($pool, $asset);
        app(IpamService::class)->release($address);

        $reused = app(IpamService::class)->allocate($pool, $asset, 'new.example.test');

        $this->assertSame($address->id, $reused->id);
        $this->assertSame('assigned', $reused->status);
        $this->assertSame('new.example.test', $reused->hostname);
    }

    public function test_overlapping_subnets_and_cross_tenant_assignments_are_rejected(): void
    {
        $team = Team::factory()->create();
        $service = app(IpamService::class);
        $pool = $service->createPool($team->id, ['name' => 'Primary', 'cidr' => '10.0.0.0/24']);

        try {
            $service->createPool($team->id, ['name' => 'Overlap', 'cidr' => '10.0.0.128/25']);
            $this->fail('Expected overlapping subnet rejection.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('overlaps', $exception->getMessage());
        }

        $foreignAsset = InfrastructureAsset::factory()->create();
        $this->expectException(InvalidArgumentException::class);
        $service->allocate($pool, $foreignAsset);
    }
}
