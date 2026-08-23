<?php

declare(strict_types=1);

namespace App\Services\ControlPanels;

use App\Models\HostingServer;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Random\RandomException;

class LiberuControlPanelClient
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
        $password = $this->generatePassword();
        $data = [
            'username' => $username,
            'domain' => $domain,
            'email' => $username.'@'.$domain,
            'password' => $password,
            'package' => $package,
            'status' => 'active',
        ];

        return $this->makeApiCall(
            'POST',
            '/api/hosting/accounts',
            $data
        );
    }

    /**
     * @throws Exception
     */
    public function suspendAccount(string $username): bool
    {
        $data = [
            'username' => $username,
            'reason' => 'Non-payment',
        ];

        return $this->makeApiCall(
            'POST',
            '/api/hosting/accounts/'.$username.'/suspend',
            $data
        );
    }

    /**
     * @throws Exception
     */
    public function unsuspendAccount(string $username): bool
    {
        return $this->makeApiCall(
            'POST',
            '/api/hosting/accounts/'.$username.'/unsuspend',
            [
                'username' => $username,
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function changePackage(string $username, $newPackage): bool
    {
        $data = [
            'username' => $username,
            'package' => $newPackage,
        ];

        return $this->makeApiCall(
            'PUT',
            '/api/hosting/accounts/'.$username.'/package',
            $data
        );
    }

    /**
     * @throws Exception
     */
    public function terminateAccount(string $username): bool
    {
        return $this->makeApiCall(
            'DELETE',
            '/api/hosting/accounts/'.$username,
            [
                'username' => $username,
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function addAddon(string $username, $addon): bool
    {
        $data = [
            'username' => $username,
            'addon' => $addon,
        ];

        return $this->makeApiCall(
            'POST',
            '/api/hosting/accounts/'.$username.'/addons',
            $data
        );
    }

    public function removeAddon(string $username, string $addon): bool
    {
        $data = [
            'username' => $username,
            'addon' => $addon,
        ];

        return $this->makeApiCall(
            'DELETE',
            '/api/hosting/accounts/'.$username.'/addons/'.$addon,
            $data
        );
    }

    protected function makeApiCall(string $method, string $endpoint, $data = []): bool
    {
        if (! $this->server) {
            throw new Exception('Server not configured');
        }

        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ];

            if (! empty($data)) {
                $options['json'] = $data;
            }

            $url = rtrim(
                (string) $this->server->api_url,
                '/'
            ).$endpoint;
            $response = $this->client->request(
                $method,
                $url,
                $options
            );

            $result = json_decode(
                $response->getBody()->getContents(),
                true
            );

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                Log::info(
                    'Liberu Control Panel API call successful',
                    [
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'server' => $this->server->hostname,
                    ]
                );

                return true;
            }

            Log::error(
                'Liberu Control Panel API call failed',
                [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'server' => $this->server->hostname,
                    'error' => $result['message'] ?? 'Unknown error',
                ]
            );

            return false;

        } catch (GuzzleException $e) {
            Log::error(
                'Liberu Control Panel API call error',
                [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'server' => $this->server->hostname,
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * @throws RandomException
     */
    protected function generatePassword(): string
    {
        return bin2hex(random_bytes(12));
    }
}
