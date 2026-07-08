<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Models\LicenseInstance;
use Illuminate\Support\Str;
use RuntimeException;

class LicenseService
{
    /**
     * Reissue a license — clear its registered instances so it can be activated
     * on a fresh install. Abuse-guarded: too many reissues within the window
     * are rejected.
     */
    public function reissue(License $license, ?int $reissuedBy = null): void
    {
        $limit = (int) config('licensing.reissue_limit', 3);
        $windowHours = (int) config('licensing.reissue_window_hours', 24);

        $recent = $license->reissues()
            ->where('created_at', '>=', now()->subHours($windowHours))
            ->count();

        if ($recent >= $limit) {
            throw new RuntimeException('License reissue limit reached; try again later.');
        }

        $license->instances()->delete();
        $license->update(['status' => LicenseStatus::Active]);
        $license->reissues()->create(['reissued_by' => $reissuedBy]);
    }

    /**
     * Generate a unique, human-readable license key: PREFIX-XXXXX-XXXXX-...
     */
    public function generate(string $prefix = 'LIC', int $segments = 4, int $segmentLength = 5): string
    {
        do {
            $parts = [];
            for ($i = 0; $i < $segments; $i++) {
                $parts[] = strtoupper(Str::random($segmentLength));
            }
            $key = $prefix.'-'.implode('-', $parts);
        } while (License::withTrashed()->where('license_key', $key)->exists());

        return $key;
    }

    /**
     * Validate a license key for a calling instance. Registers/refreshes the
     * instance (enforcing max_instances) and returns the verdict + a signed
     * offline key the SDK can cache.
     *
     * @param  array{identifier?: string, ip_address?: string}  $instance
     * @return array{valid: bool, status: string, reason?: string, data?: array{local_key: string}}
     */
    public function validate(string $licenseKey, array $instance): array
    {
        $identifier = $instance['identifier'] ?? '';

        $license = License::where('license_key', $licenseKey)->first();

        if ($license === null || ! $license->isUsable()) {
            return [
                'valid' => false,
                'status' => $license?->status->value ?? 'unknown',
            ];
        }

        $existing = $license->instances()->where('identifier', $identifier)->first();

        if ($existing === null && $license->instances()->count() >= $license->max_instances) {
            return [
                'valid' => false,
                'status' => $license->status->value,
                'reason' => 'instance_limit_reached',
            ];
        }

        $localKey = $this->localKey($licenseKey, $identifier);

        LicenseInstance::updateOrCreate(
            ['license_id' => $license->id, 'identifier' => $identifier],
            [
                'ip_address' => $instance['ip_address'] ?? null,
                'last_validated_at' => now(),
                'local_key' => $localKey,
            ],
        );

        return [
            'valid' => true,
            'status' => $license->status->value,
            'data' => ['local_key' => $localKey],
        ];
    }

    /**
     * Verify a cached offline key — lets the SDK keep working while the platform
     * is unreachable. ponytail: deterministic HMAC, no TTL; add expiry+rotation
     * if offline keys must age out.
     */
    public function verifyLocalKey(string $licenseKey, string $identifier, string $localKey): bool
    {
        return hash_equals($this->localKey($licenseKey, $identifier), $localKey);
    }

    private function localKey(string $licenseKey, string $identifier): string
    {
        return hash_hmac('sha256', $licenseKey.'|'.$identifier, (string) config('app.key'));
    }
}
