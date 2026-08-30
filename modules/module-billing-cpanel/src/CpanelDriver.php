<?php

declare(strict_types=1);

namespace Liberu\Billing\Cpanel;

use Liberu\Billing\Hosting\Contracts\ControlPanelDriver;
use Liberu\Billing\Hosting\Support\AbstractControlPanelDriver;
use RuntimeException;

final class CpanelDriver extends AbstractControlPanelDriver implements ControlPanelDriver
{
    public function key(): string
    {
        return 'cpanel';
    }

    public function provision(array $attributes): array
    {
        $account = $this->account($attributes);
        $package = trim((string) ($account['package'] ?? ''));
        if ($package === '') {
            throw new \InvalidArgumentException('A cPanel package is required.');
        }

        $this->call($attributes, '/json-api/createacct', [
            'username' => $account['username'],
            'domain' => $account['domain'],
            'plan' => $package,
            'featurelist' => $package,
            'password' => $this->password($account),
            'contactemail' => $account['email'] ?? $account['username'].'@'.$account['domain'],
            'customip' => $this->server($attributes)['ip_address'] ?? '',
            'owner' => $this->server($attributes)['username'] ?? '',
        ]);

        return $this->result($account['username'], 'active');
    }

    public function suspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, '/json-api/suspendacct', ['user' => $account['username'], 'reason' => $account['reason'] ?? 'Billing policy']);

        return $this->result($account['username'], 'suspended');
    }

    public function unsuspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, '/json-api/unsuspendacct', ['user' => $account['username']]);

        return $this->result($account['username'], 'active');
    }

    public function changePackage(array $attributes): array
    {
        $account = $this->account($attributes);
        $package = trim((string) ($account['package'] ?? $account['new_package'] ?? ''));
        if ($package === '') {
            throw new \InvalidArgumentException('A new cPanel package is required.');
        }
        $this->call($attributes, '/json-api/changepackage', ['user' => $account['username'], 'pkg' => $package]);

        return $this->result($account['username'], 'active', ['package' => $package]);
    }

    public function terminate(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, '/json-api/removeacct', ['user' => $account['username'], 'keepdns' => 0]);

        return $this->result($account['username'], 'terminated');
    }

    public function addAddon(array $attributes): array
    {
        return $this->addon($attributes, true);
    }

    public function removeAddon(array $attributes): array
    {
        return $this->addon($attributes, false);
    }

    private function addon(array $attributes, bool $enabled): array
    {
        $account = $this->account($attributes);
        $addon = trim((string) ($account['addon'] ?? ''));
        if ($addon === '') {
            throw new \InvalidArgumentException('A cPanel addon is required.');
        }
        $this->call($attributes, '/json-api/modifyacct', ['user' => $account['username'], 'FEATURE-'.strtoupper($addon) => $enabled ? 1 : 0]);

        return $this->result($account['username'], 'active', ['addon' => $addon, 'enabled' => $enabled]);
    }

    private function call(array $attributes, string $endpoint, array $params): array
    {
        $server = $this->server($attributes);
        if (! isset($server['api_url'])) {
            $server['api_url'] = 'https://'.($server['hostname'] ?? '').':2087';
        }
        $response = $this->requestJson('GET', $endpoint, $server, [
            'headers' => ['Authorization' => 'whm '.trim((string) ($server['username'] ?? 'root')).':'.$this->token($server)],
            'query' => array_merge(['api.version' => 1], $params),
        ]);
        $result = data_get($response, 'metadata.result');
        if ($result !== null && (int) $result !== 1) {
            throw new RuntimeException((string) (data_get($response, 'metadata.reason') ?? 'cPanel rejected the operation.'));
        }

        return $response;
    }

    private function result(mixed $username, string $status, array $extra = []): array
    {
        return array_merge(['provider' => $this->key(), 'external_id' => (string) $username, 'status' => $status], $extra);
    }
}
