<?php

namespace App\Services;

use App\Models\ClientContact;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClientContactService
{
    /**
     * Get all contacts for a customer
     */
    public function getContacts(Customer $customer): Collection
    {
        return $customer->contacts()->orderByDesc('is_primary')->orderBy('first_name')->get();
    }

    /**
     * Create a new contact for a customer
     */
    public function createContact(Customer $customer, array $data): ClientContact
    {
        /** @var ClientContact */
        return DB::transaction(
            function () use ($customer, $data) {
                if (! empty($data['is_primary'])) {
                    $customer->contacts()->update(['is_primary' => false]);
                }

                return $customer->contacts()->create($data);
            }
        );
    }

    /**
     * Update an existing contact
     */
    public function updateContact(ClientContact $contact, array $data): ClientContact
    {
        return DB::transaction(
            function () use ($contact, $data) {
                if (! empty($data['is_primary'])) {
                    $contact->customer->contacts()
                        ->where(
                            'id',
                            '!=',
                            $contact->id
                        )
                        ->update(['is_primary' => false]);
                }

                $contact->update($data);

                return $contact->fresh();
            }
        );
    }

    /**
     * Delete a contact
     */
    public function deleteContact(ClientContact $contact): void
    {
        if ($contact->is_primary) {
            throw new RuntimeException('Cannot delete the primary contact.');
        }

        $contact->delete();
    }

    /**
     * Make a contact the primary contact for a customer
     */
    public function makePrimary(ClientContact $contact): ClientContact
    {
        return DB::transaction(
            function () use ($contact) {
                $contact->customer->contacts()->update(['is_primary' => false]);
                $contact->update(['is_primary' => true]);

                return $contact->fresh();
            }
        );
    }

    /**
     * Get the primary contact for a customer
     */
    public function getPrimaryContact(Customer $customer): ?ClientContact
    {
        /** @var ClientContact|null */
        return $customer->contacts()->where(
            'is_primary',
            true
        )->first();
    }
}
