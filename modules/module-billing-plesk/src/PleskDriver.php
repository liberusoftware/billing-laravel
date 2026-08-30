<?php

declare(strict_types=1);

namespace Liberu\Billing\Plesk;

use Liberu\Billing\Hosting\Contracts\ControlPanelDriver;
use Liberu\Billing\Hosting\Support\AbstractControlPanelDriver;
use RuntimeException;
use SimpleXMLElement;

final class PleskDriver extends AbstractControlPanelDriver implements ControlPanelDriver
{
    public function key(): string
    {
        return 'plesk';
    }

    public function provision(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, 'webspace.add', [
            'gen_setup' => ['name' => $account['domain'], 'owner-login' => $account['username'], 'owner-password' => $this->password($account), 'ip_address' => $this->server($attributes)['ip_address'] ?? ''],
            'hosting' => ['vrt_hst' => ['property' => [['name' => 'ftp_login', 'value' => $account['username']], ['name' => 'ftp_password', 'value' => $this->password($account)], ['name' => 'php', 'value' => 'true'], ['name' => 'ssl', 'value' => 'true']]]],
            'plan-name' => $account['package'] ?? '',
        ]);

        return $this->result($account['username'], 'active');
    }

    public function suspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, 'customer.set', ['filter' => ['login' => $account['username']], 'values' => ['status' => '16'], 'general' => ['status' => 'suspended']]);

        return $this->result($account['username'], 'suspended');
    }

    public function unsuspend(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, 'customer.set', ['filter' => ['login' => $account['username']], 'values' => ['status' => '0'], 'general' => ['status' => 'active']]);

        return $this->result($account['username'], 'active');
    }

    public function changePackage(array $attributes): array
    {
        $account = $this->account($attributes);
        $package = trim((string) ($account['package'] ?? $account['new_package'] ?? ''));
        if ($package === '') {
            throw new \InvalidArgumentException('A new Plesk service plan is required.');
        }
        $this->call($attributes, 'service-plan.set', ['filter' => ['owner-login' => $account['username']], 'values' => ['name' => $package]]);

        return $this->result($account['username'], 'active', ['package' => $package]);
    }

    public function terminate(array $attributes): array
    {
        $account = $this->account($attributes);
        $this->call($attributes, 'webspace.del', ['filter' => ['owner-login' => $account['username']]]);

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
            throw new \InvalidArgumentException('A Plesk addon is required.');
        }
        $this->call($attributes, $enabled ? 'site-addon.add' : 'site-addon.del', ['filter' => ['owner-login' => $account['username']], 'addon' => ['name' => $addon]]);

        return $this->result($account['username'], 'active', ['addon' => $addon, 'enabled' => $enabled]);
    }

    private function call(array $attributes, string $command, array $parameters): void
    {
        $server = $this->server($attributes);
        if (! isset($server['api_url'])) {
            $server['api_url'] = 'https://'.($server['hostname'] ?? '').':8443';
        }
        $xml = new SimpleXMLElement('<packet version="1.6.9.1"/>');
        $commandNode = $xml->addChild($command);
        $this->append($commandNode, $parameters);
        $body = $this->requestXml('POST', '/api/v2/cli/server/', $server, $xml->asXML() ?: '', ['KEY' => $this->token($server), 'HTTP_AUTH_KEY' => $this->token($server)]);
        $response = new SimpleXMLElement($body);
        $status = $response->xpath('//status')[0] ?? null;
        if ($status !== null && (string) $status !== 'ok') {
            $message = $response->xpath('//errtext')[0] ?? 'Plesk rejected the operation.';
            throw new RuntimeException((string) $message);
        }
        $error = $response->xpath('//errcode')[0] ?? null;
        if ($error !== null && (int) $error !== 0) {
            $message = $response->xpath('//errtext')[0] ?? 'Plesk rejected the operation.';
            throw new RuntimeException((string) $message);
        }
    }

    private function append(SimpleXMLElement $parent, array $values): void
    {
        foreach ($values as $name => $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $item) {
                        $child = $parent->addChild((string) $name);
                        if (is_array($item)) {
                            $this->append($child, $item);
                        } else {
                            $child[0] = htmlspecialchars((string) $item, ENT_XML1);
                        }
                    }
                } else {
                    $child = $parent->addChild((string) $name);
                    $this->append($child, $value);
                }

                continue;
            }
            $child = $parent->addChild((string) $name);
            $child[0] = htmlspecialchars((string) $value, ENT_XML1);
        }
    }

    private function result(mixed $username, string $status, array $extra = []): array
    {
        return array_merge(['provider' => $this->key(), 'external_id' => (string) $username, 'status' => $status], $extra);
    }
}
