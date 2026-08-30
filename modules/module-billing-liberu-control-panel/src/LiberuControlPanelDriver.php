<?php

declare(strict_types=1);

namespace Liberu\Billing\LiberuControlPanel;

use Liberu\Billing\Hosting\Contracts\ControlPanelDriver;
use Liberu\Billing\Hosting\Support\AbstractControlPanelDriver;

final class LiberuControlPanelDriver extends AbstractControlPanelDriver implements ControlPanelDriver
{
    public function key(): string
    {
        return 'liberu';
    }

    public function provision(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, 'POST', '/api/hosting/accounts', ['username' => $account['username'], 'domain' => $account['domain'], 'email' => $account['email'] ?? $account['username'].'@'.$account['domain'], 'password' => $this->password($account), 'package' => $account['package'] ?? '', 'status' => 'active']);

        return $this->result($account['username'], 'active');
    }

    public function suspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, 'POST', '/api/hosting/accounts/'.$this->segment($account['username']).'/suspend', ['username' => $account['username'], 'reason' => $account['reason'] ?? 'Billing policy']);

        return $this->result($account['username'], 'suspended');
    }

    public function unsuspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, 'POST', '/api/hosting/accounts/'.$this->segment($account['username']).'/unsuspend', ['username' => $account['username']]);

        return $this->result($account['username'], 'active');
    }

    public function changePackage(array $attributes): array
    {
        $account = $this->account($attributes);
        $package = trim((string) ($account['package'] ?? $account['new_package'] ?? ''));
        if ($package === '') {
            throw new \InvalidArgumentException('A new Liberu Control Panel package is required.');
        }
        $this->call($attributes, 'PUT', '/api/hosting/accounts/'.$this->segment($account['username']).'/package', ['username' => $account['username'], 'package' => $package]);

        return $this->result($account['username'], 'active', ['package' => $package]);
    }

    public function terminate(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, 'DELETE', '/api/hosting/accounts/'.$this->segment($account['username']), ['username' => $account['username']]);

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
            throw new \InvalidArgumentException('A Liberu Control Panel addon is required.');
        }
        $path = '/api/hosting/accounts/'.$this->segment($account['username']).'/addons';
        $this->call($attributes, $enabled ? 'POST' : 'DELETE', $enabled ? $path : $path.'/'.$this->segment($addon), ['username' => $account['username'], 'addon' => $addon]);

        return $this->result($account['username'], 'active', ['addon' => $addon, 'enabled' => $enabled]);
    }

    private function call(array $attributes, string $method, string $endpoint, array $payload): array
    {
        $server = $this->server($attributes);
        $response = $this->requestJson($method, $endpoint, $server, [
            'headers' => ['Authorization' => 'Bearer '.$this->token($server), 'Content-Type' => 'application/json'],
            'json' => $payload,
        ]);

        return $response;
    }

    private function segment(mixed $value): string
    {
        return rawurlencode((string) $value);
    }

    private function result(mixed $username, string $status, array $extra = []): array
    {
        return array_merge(['provider' => $this->key(), 'external_id' => (string) $username, 'status' => $status], $extra);
    }
}
