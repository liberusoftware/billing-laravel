<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Registrars\EnomClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnomDnsWhoisTest extends TestCase
{
    public function test_dns_records_fetched(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount>'
                .'<host><HostID>1</HostID><HostName>www</HostName><RecordType>A</RecordType><Address>1.2.3.4</Address><TTL>3600</TTL></host>'
                .'<host><HostID>2</HostID><HostName>@</HostName><RecordType>MX</RecordType><Address>mail.example.com</Address><TTL>7200</TTL></host>'
                .'</interface-response>'),
        ]);

        $records = app(EnomClient::class)->getDnsRecords('example.com');

        $this->assertCount(2, $records);
        $this->assertSame('A', $records[0]['type']);
        $this->assertSame('1.2.3.4', $records[0]['content']);
    }

    public function test_dns_record_added(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount></interface-response>'),
        ]);

        $ok = app(EnomClient::class)->addDnsRecord('example.com', [
            'type' => 'A', 'name' => 'blog', 'content' => '5.6.7.8',
        ]);

        $this->assertTrue($ok);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'command=SetHosts'));
    }

    public function test_whois_contacts_updated(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount></interface-response>'),
        ]);

        $ok = app(EnomClient::class)->updateWhoisContacts('example.com', [
            'Registrant' => ['first_name' => 'Jane'],
        ]);

        $this->assertTrue($ok);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'command=Contacts'));
    }
}
