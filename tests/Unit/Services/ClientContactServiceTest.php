<?php

namespace Tests\Unit\Services;

use App\Models\ClientContact;
use App\Models\Customer;
use App\Services\ClientContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientContactServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClientContactService $contactService;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contactService = new ClientContactService;
        $this->customer = Customer::factory()->create();
    }

    public function test_can_create_contact(): void
    {
        $contact = $this->contactService->createContact($this->customer, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1-555-0100',
            'is_primary' => true,
            'can_view_invoices' => true,
            'can_make_payments' => true,
            'can_manage_services' => false,
        ]);

        $this->assertInstanceOf(ClientContact::class, $contact);
        $this->assertEquals('John', $contact->first_name);
        $this->assertEquals('Doe', $contact->last_name);
        $this->assertEquals('john.doe@example.com', $contact->email);
        $this->assertTrue($contact->is_primary);
        $this->assertEquals($this->customer->id, $contact->customer_id);
    }

    public function test_creating_primary_contact_demotes_existing_primary(): void
    {
        $first = $this->contactService->createContact($this->customer, [
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
            'is_primary' => true,
        ]);

        $second = $this->contactService->createContact($this->customer, [
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => 'bob@example.com',
            'is_primary' => true,
        ]);

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->is_primary);
    }

    public function test_can_update_contact(): void
    {
        $contact = ClientContact::create([
            'customer_id' => $this->customer->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'is_primary' => false,
        ]);

        $updated = $this->contactService->updateContact($contact, [
            'first_name' => 'Janet',
            'phone' => '+1-555-0200',
        ]);

        $this->assertEquals('Janet', $updated->first_name);
        $this->assertEquals('+1-555-0200', $updated->phone);
    }

    public function test_can_delete_non_primary_contact(): void
    {
        $contact = ClientContact::create([
            'customer_id' => $this->customer->id,
            'first_name' => 'Delete',
            'last_name' => 'Me',
            'email' => 'delete@example.com',
            'is_primary' => false,
        ]);

        $this->contactService->deleteContact($contact);

        $this->assertDatabaseMissing('client_contacts', ['id' => $contact->id]);
    }

    public function test_cannot_delete_primary_contact(): void
    {
        $contact = ClientContact::create([
            'customer_id' => $this->customer->id,
            'first_name' => 'Primary',
            'last_name' => 'Contact',
            'email' => 'primary@example.com',
            'is_primary' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->contactService->deleteContact($contact);
    }

    public function test_can_make_contact_primary(): void
    {
        $first = ClientContact::create([
            'customer_id' => $this->customer->id,
            'first_name' => 'First',
            'last_name' => 'Contact',
            'email' => 'first@example.com',
            'is_primary' => true,
        ]);

        $second = ClientContact::create([
            'customer_id' => $this->customer->id,
            'first_name' => 'Second',
            'last_name' => 'Contact',
            'email' => 'second@example.com',
            'is_primary' => false,
        ]);

        $this->contactService->makePrimary($second);

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_can_get_primary_contact(): void
    {
        ClientContact::create([
            'customer_id' => $this->customer->id,
            'first_name' => 'Not',
            'last_name' => 'Primary',
            'email' => 'notprimary@example.com',
            'is_primary' => false,
        ]);

        ClientContact::create([
            'customer_id' => $this->customer->id,
            'first_name' => 'Is',
            'last_name' => 'Primary',
            'email' => 'primary@example.com',
            'is_primary' => true,
        ]);

        $primary = $this->contactService->getPrimaryContact($this->customer);

        $this->assertInstanceOf(ClientContact::class, $primary);
        $this->assertEquals('Is', $primary->first_name);
    }

    public function test_get_primary_contact_returns_null_when_none(): void
    {
        $primary = $this->contactService->getPrimaryContact($this->customer);

        $this->assertNull($primary);
    }

    public function test_can_get_all_contacts_for_customer(): void
    {
        ClientContact::create([
            'customer_id' => $this->customer->id,
            'first_name' => 'Contact',
            'last_name' => 'One',
            'email' => 'one@example.com',
            'is_primary' => true,
        ]);

        ClientContact::create([
            'customer_id' => $this->customer->id,
            'first_name' => 'Contact',
            'last_name' => 'Two',
            'email' => 'two@example.com',
            'is_primary' => false,
        ]);

        $contacts = $this->contactService->getContacts($this->customer);

        $this->assertCount(2, $contacts);
    }

    public function test_full_name_attribute(): void
    {
        $contact = new ClientContact([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $contact->full_name);
    }
}
