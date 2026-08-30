<?php

declare(strict_types=1);

namespace Liberu\Billing\Virtualmin;

use Liberu\Billing\Hosting\Contracts\ControlPanelDriver;
use Liberu\Billing\Hosting\Support\AbstractControlPanelDriver;
use RuntimeException;

class VirtualminDriver extends AbstractControlPanelDriver implements ControlPanelDriver
{
    public function key(): string
    {
        return 'virtualmin';
    }

    public function provision(array $attributes): array
    {
        $account = $this->account($attributes);
        $password = $this->password($account);
        $this->call($attributes, ['program' => 'create-domain', 'domain' => $account['domain'], 'user' => $account['username'], 'pass' => $password, 'email' => $account['email'] ?? $account['username'].'@'.$account['domain'], 'plan' => $account['package'] ?? '', 'mysql' => '', 'web' => '', 'dns' => '', 'mail' => '', 'unix' => '']);

        return $this->result($account['username'], 'active');
    }

    public function suspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, ['program' => 'disable-domain', 'user' => $account['username'], 'why' => $account['reason'] ?? 'Billing policy']);

        return $this->result($account['username'], 'suspended');
    }

    public function unsuspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, ['program' => 'enable-domain', 'user' => $account['username']]);

        return $this->result($account['username'], 'active');
    }

    public function changePackage(array $attributes): array
    {
        $account = $this->account($attributes);
        $package = trim((string) ($account['package'] ?? $account['new_package'] ?? ''));
        if ($package === '') {
            throw new \InvalidArgumentException('A new Virtualmin plan is required.');
        }
        $this->call($attributes, ['program' => 'modify-domain', 'user' => $account['username'], 'apply-plan' => $package]);

        return $this->result($account['username'], 'active', ['package' => $package]);
    }

    public function terminate(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, ['program' => 'delete-domain', 'user' => $account['username']]);

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
            throw new \InvalidArgumentException('A Virtualmin addon is required.');
        }
        $this->call($attributes, ['program' => 'modify-domain', 'user' => $account['username'], $enabled ? 'enable-feature' : 'disable-feature' => $addon]);

        return $this->result($account['username'], 'active', ['addon' => $addon, 'enabled' => $enabled]);
    }

    private function call(array $attributes, array $params): array
    {
        $server = $this->server($attributes);
        if (! isset($server['api_url'])) {
            $server['api_url'] = 'https://'.($server['hostname'] ?? '').':10000';
        }
        $username = trim((string) ($server['username'] ?? ''));
        if ($username === '') {
            throw new \InvalidArgumentException('A Virtualmin username is required.');
        }
        $response = $this->requestForm('POST', '/virtual-server/remote.cgi', $server, [
            'headers' => ['Authorization' => 'Basic '.base64_encode($username.':'.$this->token($server))],
            'form_params' => array_merge(['json' => 1], $params),
        ]);
        if (($response['status'] ?? null) !== 'success') {
            throw new RuntimeException($response['error'] ?? 'Virtualmin rejected the operation.');
        }

        return $response;
    }

    private function result(mixed $username, string $status, array $extra = []): array
    {
        return array_merge(['provider' => $this->key(), 'external_id' => (string) $username, 'status' => $status], $extra);
    }
}
