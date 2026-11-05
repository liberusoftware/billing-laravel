<?php

declare(strict_types=1);

namespace App\Services\ControlPanels;

use App\Models\HostingServer;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Random\RandomException;

class PleskClient
{
    protected Client $client;

    protected $server;

    protected $apiKey;

    protected ?\SimpleXMLElement $lastResponse = null;

    public function __construct()
    {
        $this->client = new Client([
            'connect_timeout' => 5,
            'timeout' => 30,
            'allow_redirects' => false,
        ]);
    }

    public function setServer(HostingServer $server): void
    {
        $this->server = $server;
        $this->apiKey = $server->api_token;
    }

    /**
     * @throws Exception
     */
    public function createAccount($username, $domain, $package): bool
    {
        if (! $this->makeApiCall($this->buildXmlRequest('customer.add', [
            'gen_info' => [
                'pname' => $username,
                'login' => $username,
                'passwd' => $this->generatePassword(),
                'email' => $username.'@'.$domain,
            ],
        ]))) {
            return false;
        }

        return $this->makeApiCall($this->buildXmlRequest('webspace.add', [
            'gen_setup' => [
                'name' => $domain,
                'owner-login' => $username,
                'htype' => 'vrt_hst',
                'ip_address' => $this->server->ip_address,
            ],
            'hosting' => [
                'vrt_hst' => [
                    'property' => [
                        ['name' => 'ftp_login', 'value' => $username],
                        ['name' => 'ftp_password', 'value' => $this->generatePassword()],
                    ],
                    'ip_address' => $this->server->ip_address,
                ],
            ],
            'plan-name' => $package,
        ]));
    }

    /**
     * @throws Exception
     */
    public function suspendAccount($username): bool
    {
        $xml = $this->buildXmlRequest(
            'customer.set',
            [
                'filter' => ['login' => $username],
                'gen_info' => ['status' => '16'],
            ]
        );

        return $this->makeApiCall($xml);
    }

    /**
     * @throws Exception
     */
    public function unsuspendAccount($username): bool
    {
        $xml = $this->buildXmlRequest(
            'customer.set',
            [
                'filter' => ['login' => $username],
                'gen_info' => ['status' => '0'],
            ]
        );

        return $this->makeApiCall($xml);
    }

    /**
     * @throws Exception
     */
    public function changePackage($username, $newPackage): bool
    {
        return $this->changePlan($username, $newPackage, 'service-plan', 'switch-subscription');
    }

    /**
     * @throws Exception
     */
    public function terminateAccount($username): bool
    {
        $xml = $this->buildXmlRequest(
            'webspace.del',
            [
                'filter' => ['owner-login' => $username],
            ]
        );

        return $this->makeApiCall($xml);
    }

    /**
     * @throws Exception
     */
    public function addAddon($username, $addon): bool
    {
        return $this->changePlan($username, $addon, 'service-plan-addon', 'add-subscription');
    }

    public function removeAddon($username, $addon): bool
    {
        return $this->changePlan($username, $addon, 'service-plan-addon', 'remove-subscription');
    }

    private function changePlan(string $username, string $plan, string $operator, string $operation): bool
    {
        if (! $this->makeApiCall($this->buildXmlRequest($operator.'.get', ['filter' => ['name' => $plan]]))) {
            return false;
        }
        $guids = $this->lastResponse?->xpath('//result/guid') ?: [];
        if (count($guids) !== 1 || (string) $guids[0] === '') {
            return false;
        }

        return $this->makeApiCall($this->buildXmlRequest('webspace.'.$operation, [
            'filter' => ['owner-login' => $username],
            'plan-guid' => (string) $guids[0],
        ]));
    }

    protected function makeApiCall($xml): bool
    {
        if (! $this->server) {
            throw new Exception('Server not configured');
        }

        $this->lastResponse = null;

        try {
            $response = $this->client->request(
                'POST',
                'https://'.$this->server->hostname.':8443/enterprise/control/agent.php',
                [
                    'headers' => [
                        'Content-Type' => 'text/xml',
                        'HTTP_AUTH_KEY' => $this->apiKey,
                    ],
                    'body' => $xml,
                ]
            );

            $previousErrors = libxml_use_internal_errors(true);
            try {
                $result = simplexml_load_string((string) $response->getBody(), \SimpleXMLElement::class, LIBXML_NONET);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previousErrors);
            }
            $statuses = $result === false ? [] : $result->xpath('//result/status');

            if ($statuses !== [] && collect($statuses)->every(fn ($status): bool => (string) $status === 'ok')) {
                $this->lastResponse = $result;
                Log::info(
                    'Plesk API call successful',
                    ['server' => $this->server->hostname]
                );

                return true;
            }

            Log::error(
                'Plesk API call failed',
                [
                    'server' => $this->server->hostname,
                    'error' => 'The Plesk operation failed or returned an invalid response.',
                ]
            );

            return false;

        } catch (GuzzleException $e) {
            Log::error(
                'Plesk API call error',
                [
                    'server' => $this->server->hostname,
                    'error' => $e::class,
                ]
            );

            return false;
        }
    }

    protected function buildXmlRequest($command, $params): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<packet version="1.6.9.1">';
        [$operator, $operation] = explode('.', $command, 2);
        $xml .= "<{$operator}><{$operation}>";
        $xml .= $this->arrayToXml($params);
        $xml .= "</{$operation}></{$operator}>";

        return $xml.'</packet>';
    }

    protected function arrayToXml($array): string
    {
        $xml = '';
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    // Repeated element: emit one <key>…</key> per list item
                    // (e.g. multiple <property>/<limit> blocks), instead of
                    // collapsing them into invalid <0>/<1> numeric tags.
                    foreach ($value as $item) {
                        $xml .= "<{$key}>".(is_array($item) ? $this->arrayToXml($item) : htmlspecialchars((string) $item, ENT_XML1 | ENT_QUOTES, 'UTF-8'))."</{$key}>";
                    }
                } else {
                    $xml .= "<{$key}>".$this->arrayToXml($value)."</{$key}>";
                }
            } else {
                $xml .= "<{$key}>".htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8')."</{$key}>";
            }
        }

        return $xml;
    }

    /**
     * @throws RandomException
     */
    protected function generatePassword(): string
    {
        return bin2hex(random_bytes(7));
    }
}
