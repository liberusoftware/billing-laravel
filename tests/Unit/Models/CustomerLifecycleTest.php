<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ContactType;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Models\ClientContact;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_profile_casts_type_lifecycle_tags_and_custom_fields(): void
    {
        $customer = Customer::factory()->create([
            'customer_type' => CustomerType::WholesalePartner,
            'lifecycle_status' => CustomerStatus::PendingVerification,
            'tags' => ['priority', 'reseller'],
            'custom_fields' => ['company_number' => 'ACME-42'],
        ])->refresh();

        $this->assertSame(CustomerType::WholesalePartner, $customer->customer_type);
        $this->assertSame(CustomerStatus::PendingVerification, $customer->lifecycle_status);
        $this->assertSame(['priority', 'reseller'], $customer->tags);
        $this->assertSame('ACME-42', $customer->custom_fields['company_number']);
    }

    public function test_customer_lifecycle_transition_records_when_status_changed(): void
    {
        $customer = Customer::factory()->create([
            'lifecycle_status' => CustomerStatus::Prospect,
            'status_changed_at' => null,
        ]);

        $this->assertTrue($customer->transitionTo(CustomerStatus::Active));
        $this->assertSame(CustomerStatus::Active, $customer->refresh()->lifecycle_status);
        $this->assertNotNull($customer->status_changed_at);
    }

    public function test_contacts_support_all_required_contact_roles(): void
    {
        $customer = Customer::factory()->create();

        foreach (ContactType::cases() as $type) {
            $contact = ClientContact::query()->create([
                'customer_id' => $customer->id,
                'first_name' => ucfirst($type->value),
                'last_name' => 'Contact',
                'email' => $type->value.'@example.test',
                'contact_type' => $type,
            ]);

            $this->assertSame($type, $contact->refresh()->contact_type);
        }

        $this->assertCount(count(ContactType::cases()), $customer->contacts);
    }
}
