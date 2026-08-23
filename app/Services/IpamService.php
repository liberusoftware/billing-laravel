<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IpAddress;
use App\Models\IpPool;
use App\Support\IpNetwork;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class IpamService
{
    /**
     * @param array{
     *   name: string, cidr: string, infrastructure_asset_id?: int|null,
     *   gateway?: string|null, vlan_id?: int|null
     * } $data
     */
    public function createPool(int $teamId, array $data): IpPool
    {
        $network = IpNetwork::parse($data['cidr']);
        $this->ensureNoOverlap($teamId, $network['family'], $network['first'], $network['last']);

        if (isset($data['gateway'])
            && (IpNetwork::compare($data['gateway'], $network['first']) < 0
                || IpNetwork::compare($data['gateway'], $network['last']) > 0)) {
            throw new InvalidArgumentException('The gateway must be inside the usable pool range.');
        }

        return IpPool::query()->create([
            ...$data,
            'team_id' => $teamId,
            'cidr' => $network['canonical'],
            'address_family' => $network['family'],
            'first_address' => $network['first'],
            'last_address' => $network['last'],
            'next_address' => $network['first'],
        ]);
    }

    public function allocate(IpPool $pool, Model $assignable, ?string $hostname = null): IpAddress
    {
        $assignableTeamId = $assignable->getAttribute('team_id');
        if ($assignableTeamId !== null && (int) $assignableTeamId !== $pool->team_id) {
            throw new InvalidArgumentException('An IP address cannot be assigned across tenants.');
        }

        return DB::transaction(function () use ($pool, $assignable, $hostname): IpAddress {
            $locked = IpPool::query()->lockForUpdate()->findOrFail($pool->id);
            $reusable = $locked->addresses()->where('status', 'available')->oldest('id')->first();

            if ($reusable !== null) {
                $reusable->update($this->assignmentData($assignable, $hostname));

                return $reusable->refresh();
            }

            $candidate = $locked->next_address;
            while ($candidate !== null && IpNetwork::compare($candidate, $locked->last_address) <= 0) {
                if (! IpAddress::query()->where('team_id', $locked->team_id)->where('address', $candidate)->exists()) {
                    $address = $locked->addresses()->create([
                        'team_id' => $locked->team_id,
                        'address' => $candidate,
                        ...$this->assignmentData($assignable, $hostname),
                    ]);
                    $next = IpNetwork::increment($candidate);
                    $locked->update([
                        'next_address' => $next !== null && IpNetwork::compare($next, $locked->last_address) <= 0
                            ? $next
                            : null,
                    ]);

                    return $address;
                }

                $candidate = IpNetwork::increment($candidate);
            }

            throw new RuntimeException('The IP pool is exhausted.');
        });
    }

    public function release(IpAddress $address): IpAddress
    {
        $address->update([
            'status' => 'available',
            'assignable_type' => null,
            'assignable_id' => null,
            'hostname' => null,
            'released_at' => now(),
        ]);

        return $address->refresh();
    }

    private function ensureNoOverlap(int $teamId, int $family, string $first, string $last): void
    {
        $overlaps = IpPool::query()
            ->where('team_id', $teamId)
            ->where('address_family', $family)
            ->get()
            ->contains(fn (IpPool $pool): bool => IpNetwork::compare($first, $pool->last_address) <= 0
                && IpNetwork::compare($last, $pool->first_address) >= 0);

        if ($overlaps) {
            throw new InvalidArgumentException('The subnet overlaps an existing IP pool.');
        }
    }

    /** @return array<string, mixed> */
    private function assignmentData(Model $assignable, ?string $hostname): array
    {
        return [
            'status' => 'assigned',
            'assignable_type' => $assignable->getMorphClass(),
            'assignable_id' => $assignable->getKey(),
            'hostname' => $hostname,
            'assigned_at' => now(),
            'released_at' => null,
        ];
    }
}
