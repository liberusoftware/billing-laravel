<?php

namespace App\Services\ControlPanels;

use App\Models\HostingServer;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class CpanelClient
{
    protected Client $client;

    protected $server;

    protected $apiToken;

    public function __construct()
    {
        $this->client = new Client;
    }

    public function setServer(HostingServer $server): void
    {
        $this->server = $server;
        $this->apiToken = $server->api_token;
    }

    /**
     * @throws Exception
     */
    public function createAccount(string $username, string $domain, $package): bool
    {
        $endpoint = '/json-api/createacct';
        $params = [
            'username' => $username,
            'domain' => $domain,
            'plan' => $package,
            'featurelist' => $package,
            'password' => $this->generatePassword(),
            'contactemail' => $username.'@'.$domain,
            'quota' => 0,
            // Unlimited
            'maxftp' => 'unlimited',
            'maxsql' => 'unlimited',
            'maxpop' => 'unlimited',
            'cpmod' => 'paper_lantern',
            'maxsub' => 'unlimited',
            'maxpark' => 'unlimited',
            'maxaddon' => 'unlimited',
            'bwlimit' => 0,
            // Unlimited
            'customip' => $this->server->ip_address ?? '',
            'shell' => 'n',
            'owner' => $this->server->username,
        ];

        return $this->makeApiCall(
            $endpoint,
            $params
        );
    }

    /**
     * @throws Exception
     */
    public function suspendAccount($username): bool
    {
        $endpoint = '/json-api/suspendacct';
        $params = [
            'user' => $username,
            'reason' => 'Non-payment',
            'leave-ftp' => 0,
        ];

        return $this->makeApiCall(
            $endpoint,
            $params
        );
    }

    /**
     * @throws Exception
     */
    public function unsuspendAccount($username): bool
    {
        $endpoint = '/json-api/unsuspendacct';
        $params = [
            'user' => $username,
        ];

        return $this->makeApiCall(
            $endpoint,
            $params
        );
    }

    /**
     * @throws Exception
     */
    public function changePackage($username, $newPackage): bool
    {
        $endpoint = '/json-api/changepackage';
        $params = [
            'user' => $username,
            'pkg' => $newPackage,
        ];

        return $this->makeApiCall(
            $endpoint,
            $params
        );
    }

    /**
     * @throws Exception
     */
    public function terminateAccount($username): bool
    {
        $endpoint = '/json-api/removeacct';
        $params = [
            'user' => $username,
            'keepdns' => 0,
        ];

        return $this->makeApiCall(
            $endpoint,
            $params
        );
    }

    /**
     * @throws Exception
     */
    public function addAddon($username, $addon): bool
    {
        // cPanel addons are typically features added to an account
        // This can be done by modifying account features
        $endpoint = '/json-api/modifyacct';
        $params = [
            'user' => $username,
            'FEATURE-'.strtoupper((string) $addon) => 1,
        ];

        return $this->makeApiCall(
            $endpoint,
            $params
        );
    }

    /**
     * @throws Exception
     */
    public function removeAddon($username, $addon): bool
    {
        $endpoint = '/json-api/modifyacct';
        $params = [
            'user' => $username,
            'FEATURE-'.strtoupper((string) $addon) => 0,
        ];

        return $this->makeApiCall(
            $endpoint,
            $params
        );
    }

    /**
     * Create a one-time WHM login session for the account's cPanel user and
     * return the seamless login URL (no password prompt). Returns null on
     * failure.
     *
     * @throws Exception
     */
    public function createSsoSession(string $username): ?string
    {
        if (! $this->server) {
            throw new Exception('Server not configured');
        }

        $pinnedIp = $this->validateHostname($this->server->hostname);

        try {
            // ponytail: real WHM create_user_session call here — sent via the
            // instance $client so it stays interceptable (see CpanelSsoTest).
            $response = $this->client->request(
                'GET',
                'https://'.$this->server->hostname.':2087/json-api/create_user_session',
                [
                    'headers' => [
                        'Authorization' => 'WHM '.$this->server->username.':'.$this->apiToken,
                    ],
                    'query' => [
                        'api.version' => 1,
                        'user' => $username,
                        'service' => 'cpaneld',
                    ],
                    'verify' => config(
                        'services.cpanel.ssl_verify',
                        true
                    ),
                    // ponytail: CURLOPT_RESOLVE pins DNS to the validated IP for this
                    // request, closing the rebind window (cert still verifies against
                    // the hostname). Residual: only the first validated IP is used.
                    'curl' => [CURLOPT_RESOLVE => [$this->server->hostname.':2087:'.$pinnedIp]],
                ]
            );

            $result = json_decode(
                (string) $response->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (isset($result['metadata']['result']) && $result['metadata']['result'] === 1) {
                $url = $result['data']['url'] ?? null;

                // The login URL is handed straight to redirect()->away() by the caller,
                // so it must point at the configured WHM host — otherwise it's an open redirect.
                if (! is_string($url) || parse_url($url, PHP_URL_HOST) !== $this->server->hostname) {
                    Log::error(
                        'cPanel SSO session returned an unexpected host',
                        [
                            'server' => $this->server->hostname,
                            'user' => $username,
                            'url' => $url,
                        ]
                    );

                    return null;
                }

                return $url;
            }

            Log::error(
                'cPanel SSO session creation failed',
                [
                    'server' => $this->server->hostname,
                    'user' => $username,
                    'error' => $result['metadata']['reason'] ?? 'Unknown error',
                ]
            );

            return null;

        } catch (GuzzleException $e) {
            Log::error(
                'cPanel SSO session creation error',
                [
                    'server' => $this->server->hostname,
                    'user' => $username,
                    'error' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    /**
     * @throws Exception
     */
    protected function makeApiCall(string $endpoint, $params): bool
    {
        if (! $this->server) {
            throw new Exception('Server not configured');
        }

        $pinnedIp = $this->validateHostname($this->server->hostname);

        try {
            $response = $this->client->request(
                'GET',
                'https://'.$this->server->hostname.':2087'.$endpoint,
                [
                    'headers' => [
                        'Authorization' => 'WHM '.$this->server->username.':'.$this->apiToken,
                    ],
                    'query' => $params,
                    'verify' => config(
                        'services.cpanel.ssl_verify',
                        true
                    ),
                    // ponytail: CURLOPT_RESOLVE pins DNS to the validated IP for this
                    // request, closing the rebind window (cert still verifies against
                    // the hostname). Residual: only the first validated IP is used.
                    'curl' => [CURLOPT_RESOLVE => [$this->server->hostname.':2087:'.$pinnedIp]],
                ]
            );

            $result = json_decode(
                (string) $response->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (isset($result['metadata']['result']) && $result['metadata']['result'] === 1) {
                Log::info(
                    'cPanel API call successful',
                    [
                        'endpoint' => $endpoint,
                        'server' => $this->server->hostname,
                    ]
                );

                return true;
            }

            Log::error(
                'cPanel API call failed',
                [
                    'endpoint' => $endpoint,
                    'server' => $this->server->hostname,
                    'error' => $result['metadata']['reason'] ?? 'Unknown error',
                ]
            );

            return false;

        } catch (GuzzleException $e) {
            Log::error(
                'cPanel API call error',
                [
                    'endpoint' => $endpoint,
                    'server' => $this->server->hostname,
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * Validate the hostname is public and return the validated IP to pin the
     * request to (closing the DNS-rebind TOCTOU between check and connect).
     */
    protected function validateHostname(string $hostname): string
    {
        // Literal IP: reject private/loopback/reserved directly.
        if (filter_var(
            $hostname,
            FILTER_VALIDATE_IP
        )) {
            $isPrivate = ! filter_var(
                $hostname,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
            if ($isPrivate) {
                throw new Exception('Private or reserved IP addresses are not allowed as cPanel hostnames');
            }

            return $hostname;
        }

        if (! filter_var(
            $hostname,
            FILTER_VALIDATE_DOMAIN,
            FILTER_FLAG_HOSTNAME
        )) {
            throw new Exception('Invalid cPanel hostname');
        }

        // Domain: resolve it and reject if ANY address is private/reserved, so a
        // public hostname pointing at an internal/loopback IP can't be used for SSRF.
        // Mirrors WebhookService::assertSafeUrl.
        // ponytail: IPv4-only (gethostbynamel); the first validated IP is pinned
        // into the request via CURLOPT_RESOLVE to close the DNS-rebind TOCTOU.
        $ips = gethostbynamel($hostname) ?: [];

        if ($ips === []) {
            throw new Exception('cPanel hostname could not be resolved');
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new Exception('cPanel hostname resolves to a private or reserved address');
            }
        }

        return $ips[0];
    }

    protected function generatePassword(): string
    {
        return bin2hex(random_bytes(12));
    }
}
