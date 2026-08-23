<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_created_via_factory(): void
    {
        $customer = Customer::factory()->create();

        $this->assertNotNull($customer->id);
        $this->assertNotNull($customer->name);
        $this->assertNotNull($customer->email);
    }

    public function test_customer_has_invoices_relationship(): void
    {
        $customer = Customer::factory()->create();
        Invoice::factory()->count(3)->create(['customer_id' => $customer->id]);

        $this->assertCount(3, $customer->invoices);
    }

    public function test_customer_email_is_unique(): void
    {
        $email = 'unique@example.com';
        Customer::factory()->create(['email' => $email]);

        $this->expectException(QueryException::class);
        Customer::factory()->create(['email' => $email]);
    }

    public function test_customer_has_correct_fillable_attributes(): void
    {
        $customer = new Customer;
        $fillable = $customer->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
    }
}
