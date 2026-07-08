<?php

/**
 * Liberu Billing — copy-paste license client.
 *
 * Drop this into your licensed application. It validates the license against
 * the platform and caches a signed offline key so the app keeps working while
 * the platform is unreachable.
 *
 * Usage:
 *   $client = new LiberuLicenseClient('https://billing.example.com', 'LIC-XXXXX-...');
 *   if (! $client->check()) { exit('Invalid license.'); }
 */
class LiberuLicenseClient
{
    private string $cacheFile;

    public function __construct(
        private string $baseUrl,
        private string $licenseKey,
        private ?string $identifier = null,
    ) {
        // Identify this install (domain or hostname) so the platform can enforce
        // the per-license instance limit.
        $this->identifier = $identifier ?? ($_SERVER['HTTP_HOST'] ?? gethostname());
        $this->cacheFile = sys_get_temp_dir().'/liberu-license-'.md5($this->licenseKey).'.json';
    }

    /**
     * Returns true if the license is valid. Falls back to the cached offline
     * key when the platform can't be reached.
     */
    public function check(): bool
    {
        $response = $this->post('/api/v1/license/validate', [
            'license_key' => $this->licenseKey,
            'identifier' => $this->identifier,
        ]);

        if ($response !== null) {
            $valid = ($response['valid'] ?? false) === true;
            if ($valid && isset($response['data']['local_key'])) {
                @file_put_contents($this->cacheFile, json_encode([
                    'local_key' => $response['data']['local_key'],
                    'cached_at' => time(),
                ]));
            }

            return $valid;
        }

        // Platform unreachable — trust the cached offline key if present.
        return $this->hasCachedKey();
    }

    private function hasCachedKey(): bool
    {
        if (! is_file($this->cacheFile)) {
            return false;
        }

        $data = json_decode((string) file_get_contents($this->cacheFile), true);

        return is_array($data) && ! empty($data['local_key']);
    }

    /**
     * @param  array<string, string>  $payload
     * @return array<string, mixed>|null null on transport failure
     */
    private function post(string $path, array $payload): ?array
    {
        $ch = curl_init($this->baseUrl.$path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $body = curl_exec($ch);
        $ok = $body !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) < 500;

        if (! $ok) {
            return null;
        }

        $decoded = json_decode((string) $body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
