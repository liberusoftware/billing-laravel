<?php

declare(strict_types=1);

namespace App\Services\ControlPanels;

use App\Models\HostingServer;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Random\RandomException;

class VirtualminClient
{
    protected Client $client;

    protected $server;

    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client;
    }

    public function setServer(HostingServer $server): void
    {
        $this->server = $server;
        $this->apiKey = $server->api_token;
    }

    /**
     * @throws Exception
     */
    public function createAccount(string $username, string $domain, $package): bool
    {
        $password = $this->generatePassword();
        $params = [
            'program' => 'create-domain',
            'domain' => $domain,
            'user' => $username,
            'pass' => $password,
            'email' => $username.'@'.$domain,
            'plan' => $package,
            'mysql' => '',
            'web' => '',
            'dns' => '',
            'mail' => '',
            'unix' => '',
        ];

        return $this->makeApiCall($params);
    }

    /**
     * @throws Exception
     */
    public function suspendAccount($username): bool
    {
        $params = [
            'program' => 'disable-domain',
            'user' => $username,
            'why' => 'Non-payment',
        ];

        return $this->makeApiCall($params);
    }

    /**
     * @throws Exception
     */
    public function unsuspendAccount($username): bool
    {
        $params = [
            'program' => 'enable-domain',
            'user' => $username,
        ];

        return $this->makeApiCall($params);
    }

    /**
     * @throws Exception
     */
    public function changePackage($username, $newPackage): bool
    {
        $params = [
            'program' => 'modify-domain',
            'user' => $username,
            'apply-plan' => $newPackage,
        ];

        return $this->makeApiCall($params);
    }

    /**
     * @throws Exception
     */
    public function terminateAccount($username): bool
    {
        $params = [
            'program' => 'delete-domain',
            'user' => $username,
        ];

        return $this->makeApiCall($params);
    }

    /**
     * @throws Exception
     */
    public function addAddon($username, $addon): bool
    {
        $params = [
            'program' => 'modify-domain',
            'user' => $username,
            'enable-feature' => $addon,
        ];

        return $this->makeApiCall($params);
    }

    /**
     * @throws Exception
     */
    public function removeAddon($username, $addon): bool
    {
        $params = [
            'program' => 'modify-domain',
            'user' => $username,
            'disable-feature' => $addon,
        ];

        return $this->makeApiCall($params);
    }

    protected function makeApiCall(array $params): bool
    {
        if (! $this->server) {
            throw new Exception('Server not configured');
        }

        try {
            $params['json'] = '1'; // Request JSON response

            $response = $this->client->request(
                'POST',
                'https://'.$this->server->hostname.':10000/virtual-server/remote.cgi',
                [
                    'headers' => [
                        'Authorization' => 'Basic '.base64_encode($this->server->username.':'.$this->apiKey),
                    ],
                    'form_params' => $params,
                ]
            );

            $result = json_decode(
                $response->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (isset($result['status']) && $result['status'] === 'success') {
                Log::info(
                    'Virtualmin API call successful',
                    [
                        'program' => $params['program'],
                        'server' => $this->server->hostname,
                    ]
                );

                return true;
            }

            Log::error(
                'Virtualmin API call failed',
                [
                    'program' => $params['program'],
                    'server' => $this->server->hostname,
                    'error' => $result['error'] ?? 'Unknown error',
                ]
            );

            return false;

        } catch (GuzzleException $e) {
            Log::error(
                'Virtualmin API call error',
                [
                    'program' => $params['program'] ?? 'unknown',
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
