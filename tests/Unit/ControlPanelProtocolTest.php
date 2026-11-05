<?php

declare(strict_types=1);

use App\Models\HostingServer;
use App\Services\ControlPanels\CpanelClient;
use App\Services\ControlPanels\DirectAdminClient;
use App\Services\ControlPanels\LiberuControlPanelClient;
use App\Services\ControlPanels\PleskClient;
use App\Services\ControlPanels\SoftaculousClient;
use App\Services\ControlPanels\VirtualminClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

it('bounds connections and prevents authenticated redirects for every control panel', function (string $class): void {
    $client = new $class();
    $http = (new ReflectionProperty($client, 'client'))->getValue($client);
    expect($http->getConfig('connect_timeout'))->toBe(5)
        ->and($http->getConfig('timeout'))->toBe(30)
        ->and($http->getConfig('allow_redirects'))->toBeFalse();
})->with([CpanelClient::class, DirectAdminClient::class, PleskClient::class, VirtualminClient::class, LiberuControlPanelClient::class, SoftaculousClient::class]);

it('uses the WHM API version on lifecycle requests', function (): void {
    $client = new class() extends CpanelClient
    {
        protected function validateHostname(string $hostname): string
        {
            return '8.8.8.8';
        }
    };
    $client->setServer(new HostingServer(['hostname' => 'panel.example.com', 'username' => 'root', 'api_token' => 'secret']));
    $history = [];
    $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"metadata":{"result":1}}')]));
    $stack->push(Middleware::history($history));
    (new ReflectionProperty($client, 'client'))->setValue($client, new Client(['handler' => $stack]));

    expect($client->suspendAccount('alice'))->toBeTrue();
    parse_str($history[0]['request']->getUri()->getQuery(), $query);
    expect($query['api_version'])->toBe('1')->and($query['user'])->toBe('alice');
});

it('escapes XML values and builds nested Plesk operation nodes', function (): void {
    $client = new PleskClient();
    $client->setServer(new HostingServer(['hostname' => 'panel.example.com', 'username' => 'root', 'api_token' => 'secret', 'ip_address' => '192.0.2.1']));
    $history = [];
    $body = '<packet><webspace><add><result><status>ok</status></result></add></webspace></packet>';
    $stack = HandlerStack::create(new MockHandler([new Response(200, [], $body), new Response(200, [], $body)]));
    $stack->push(Middleware::history($history));
    (new ReflectionProperty($client, 'client'))->setValue($client, new Client(['handler' => $stack]));

    expect($client->createAccount('alice', 'example.com', 'Business & Commerce'))->toBeTrue();
    $request = $history[1]['request'];
    $xml = simplexml_load_string((string) $request->getBody());
    expect($request->getUri()->getPath())->toBe('/enterprise/control/agent.php')
        ->and((string) $xml->webspace->add->{'plan-name'})->toBe('Business & Commerce');
});

it('rejects malformed or partly failed Plesk responses', function (string $body): void {
    $client = new PleskClient();
    $client->setServer(new HostingServer(['hostname' => 'panel.example.com', 'api_token' => 'secret']));
    (new ReflectionProperty($client, 'client'))->setValue($client, new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200, [], $body)]))]));

    expect($client->suspendAccount('alice'))->toBeFalse();
})->with(['<html>not XML', '<packet/>', '<packet><webspace><set><result><status>ok</status></result><result><status>error</status></result></set></webspace></packet>']);

it('honours Liberu application errors and empty successful deletion responses', function (int $status, string $body, bool $expected): void {
    $client = new LiberuControlPanelClient();
    $client->setServer(new HostingServer(['hostname' => 'panel.example.com', 'api_url' => 'https://panel.example.com', 'api_token' => 'secret']));
    (new ReflectionProperty($client, 'client'))->setValue($client, new Client(['handler' => HandlerStack::create(new MockHandler([new Response($status, [], $body)]))]));

    expect($client->terminateAccount('alice'))->toBe($expected);
})->with([[200, '{"success":false}', false], [200, 'not json', false], [204, '', true]]);

it('changes only the Plesk customer subscription rather than the shared service plan', function (string $method, string $operator, string $operation): void {
    $client = new PleskClient();
    $client->setServer(new HostingServer(['hostname' => 'panel.example.com', 'api_token' => 'secret']));
    $history = [];
    $stack = HandlerStack::create(new MockHandler([
        new Response(200, [], '<packet><'.$operator.'><get><result><status>ok</status><guid>plan-guid-123</guid></result></get></'.$operator.'></packet>'),
        new Response(200, [], '<packet><webspace><'.$operation.'><result><status>ok</status></result></'.$operation.'></webspace></packet>'),
    ]));
    $stack->push(Middleware::history($history));
    (new ReflectionProperty($client, 'client'))->setValue($client, new Client(['handler' => $stack]));

    expect($client->{$method}('alice', 'Business'))->toBeTrue();
    expect((string) $history[0]['request']->getBody())->toContain('<'.$operator.'><get>')->not->toContain('<service-plan><set>');
    $xml = simplexml_load_string((string) $history[1]['request']->getBody());
    expect((string) $xml->webspace->{$operation}->filter->{'owner-login'})->toBe('alice')
        ->and((string) $xml->webspace->{$operation}->{'plan-guid'})->toBe('plan-guid-123');
})->with([
    ['changePackage', 'service-plan', 'switch-subscription'],
    ['addAddon', 'service-plan-addon', 'add-subscription'],
    ['removeAddon', 'service-plan-addon', 'remove-subscription'],
]);

it('lets DirectAdmin enforce the purchased package resource limits', function (): void {
    $client = new DirectAdminClient();
    $client->setServer(new HostingServer(['hostname' => 'panel.example.com', 'username' => 'admin', 'api_token' => 'secret', 'ip_address' => '192.0.2.1']));
    $history = [];
    $stack = HandlerStack::create(new MockHandler([new Response(200, [], 'error=0')]));
    $stack->push(Middleware::history($history));
    (new ReflectionProperty($client, 'client'))->setValue($client, new Client(['handler' => $stack]));

    expect($client->createAccount('alice', 'example.com', 'starter'))->toBeTrue();
    parse_str((string) $history[0]['request']->getBody(), $params);
    expect($params['package'])->toBe('starter')->and($params)->not->toHaveKeys(['quota', 'bandwidth', 'nemailf']);
});
