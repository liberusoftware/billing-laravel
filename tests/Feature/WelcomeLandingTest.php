<?php

declare(strict_types=1);

it('renders the customer hosting storefront at /', function (): void {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('A home for your website.')
        ->assertSee('Explore services')
        ->assertSee('Customer portal');
});

it('links the landing navigation to plans and the customer portal', function (): void {
    $this->get('/')
        ->assertSee('href="#plans"', false)
        ->assertSee(url('/client'), false);
});

it('no longer ships the stock Laravel welcome scaffold', function (): void {
    $this->get('/')->assertDontSee("Let's get started", false);
});
