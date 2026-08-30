<?php

declare(strict_types=1);

namespace Liberu\Billing\DirectAdmin;

use Liberu\Billing\Hosting\Contracts\ControlPanelDriver;
use Liberu\Billing\Hosting\Support\AbstractControlPanelDriver;
use RuntimeException;

final class DirectAdminDriver extends AbstractControlPanelDriver implements ControlPanelDriver
{
    public function key(): string
    {
        return 'directadmin';
    }

    public function provision(array $attributes): array
    {
        $account = $this->account($attributes);
        $password = $this->password($account);
        $this->call($attributes, '/CMD_API_ACCOUNT_USER', [
            'action' => 'create', 'add' => 'Submit', 'username' => $account['username'],
            'email' => $account['email'] ?? $account['username'].'@'.$account['domain'], 'passwd' => $password,
            'passwd2' => $password, 'domain' => $account['domain'], 'package' => $account['package'] ?? '',
            'ip' => $this->server($attributes)['ip_address'] ?? '', 'notify' => 'no', 'ssl' => 'ON', 'cgi' => 'ON', 'php' => 'ON',
            'spam' => 'ON', 'quota' => 'unlimited', 'bandwidth' => 'unlimited', 'mysql' => 'ON', 'dns' => 'ON',
        ]);

        return $this->result($account['username'], 'active');
    }

    public function suspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, '/CMD_API_SELECT_USERS', ['action' => 'suspend', 'select0' => $account['username'], 'suspend_reason' => $account['reason'] ?? 'Billing policy']);

        return $this->result($account['username'], 'suspended');
    }

    public function unsuspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, '/CMD_API_SELECT_USERS', ['action' => 'unsuspend', 'select0' => $account['username']]);

        return $this->result($account['username'], 'active');
    }

    public function changePackage(array $attributes): array
    {
        $account = $this->account($attributes);
        $package = trim((string) ($account['package'] ?? $account['new_package'] ?? ''));
        if ($package === '') {
            throw new \InvalidArgumentException('A new DirectAdmin package is required.');
        }
        $this->call($attributes, '/CMD_API_MODIFY_USER', ['action' => 'package', 'user' => $account['username'], 'package' => $package]);

        return $this->result($account['username'], 'active', ['package' => $package]);
    }

    public function terminate(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, '/CMD_API_SELECT_USERS', ['confirmed' => 'yes', 'delete' => 'yes', 'select0' => $account['username']]);

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
            throw new \InvalidArgumentException('A DirectAdmin addon is required.');
        }
        $this->call($attributes, '/CMD_API_MODIFY_USER', ['action' => 'customize', 'user' => $account['username'], $enabled ? 'add' : 'remove' => $addon]);

        return $this->result($account['username'], 'active', ['addon' => $addon, 'enabled' => $enabled]);
    }

    private function call(array $attributes, string $endpoint, array $params): array
    {
        $server = $this->server($attributes);
        if (! isset($server['api_url'])) {
            $server['api_url'] = 'https://'.($server['hostname'] ?? '').':2222';
        }
        $username = trim((string) ($server['username'] ?? ''));
        if ($username === '') {
            throw new \InvalidArgumentException('A DirectAdmin username is required.');
        }
        $response = $this->requestForm('POST', $endpoint, $server, [
            'headers' => ['Authorization' => 'Basic '.base64_encode($username.':'.$this->token($server))],
            'form_params' => $params,
        ]);
        if (isset($response['error']) && $response['error'] !== '0') {
            throw new RuntimeException($response['text'] ?? 'DirectAdmin rejected the operation.');
        }

        return $response;
    }

    private function result(mixed $username, string $status, array $extra = []): array
    {
        return array_merge(['provider' => $this->key(), 'external_id' => (string) $username, 'status' => $status], $extra);
    }
}
