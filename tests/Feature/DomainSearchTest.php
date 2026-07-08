<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tld;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_search_returns_availability_and_price(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount><RRPCode>210</RRPCode></interface-response>'),
        ]);

        Tld::create([
            'name' => '.com',
            'enom_cost' => 11,
            'base_price' => 11,
            'markup_type' => 'percentage',
            'markup_value' => 10,
        ]);

        $response = $this->getJson('/domains/search?domain=foo.com');

        $response->assertOk()
            ->assertJson([
                'domain' => 'foo.com',
                'available' => true,
                'price' => 12.1,
            ]);
    }

    public function test_domain_search_requires_a_domain(): void
    {
        $this->getJson('/domains/search')->assertStatus(422);
    }
}
