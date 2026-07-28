<?php

namespace App\Http\Controllers\Api;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(
            Customer::class,
            'customer'
        );
    }

    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->where('team_id', $this->currentTeamId($request))
            ->when(
                $request->string('customer_type')->isNotEmpty(),
                fn ($query) => $query->where('customer_type', $request->string('customer_type')->toString())
            )
            ->when(
                $request->string('lifecycle_status')->isNotEmpty(),
                fn ($query) => $query->where('lifecycle_status', $request->string('lifecycle_status')->toString())
            )
            ->when(
                $request->search,
                fn ($query) => $query->where(
                    fn ($searchQuery) => $searchQuery->where(
                        'name',
                        'like',
                        "%{$request->search}%"
                    )->orWhere(
                        'email',
                        'like',
                        "%{$request->search}%"
                    )
                )
            )
            ->paginate(15);

        return response()->json($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:customers,email',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'customer_type' => ['sometimes', Rule::enum(CustomerType::class)],
                'lifecycle_status' => ['sometimes', Rule::enum(CustomerStatus::class)],
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:100',
                'custom_fields' => 'nullable|array',
            ]
        );

        $customer = new Customer($validated);
        $customer->team_id = $this->currentTeamId($request);
        $customer->save();

        return response()->json(
            $customer,
            201
        );
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $this->assertSameTeam($request, $customer);

        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $this->assertSameTeam($request, $customer);

        $validated = $request->validate(
            [
                'name' => 'string|max:255',
                'email' => 'email|unique:customers,email,'.$customer->id,
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'customer_type' => ['sometimes', Rule::enum(CustomerType::class)],
                'lifecycle_status' => ['sometimes', Rule::enum(CustomerStatus::class)],
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:100',
                'custom_fields' => 'nullable|array',
            ]
        );

        if (isset($validated['lifecycle_status'])
            && $validated['lifecycle_status'] !== $customer->lifecycle_status->value) {
            $validated['status_changed_at'] = now();
        }

        $customer->update($validated);

        return response()->json($customer);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        $this->assertSameTeam($request, $customer);
        $customer->delete();

        return response()->json(
            null,
            204
        );
    }

    public function invoices(Request $request, Customer $customer): JsonResponse
    {
        $this->assertSameTeam($request, $customer);
        $invoices = $customer->invoices()->paginate(15);

        return response()->json($invoices);
    }

    public function subscriptions(Request $request, Customer $customer): JsonResponse
    {
        $this->assertSameTeam($request, $customer);
        $subscriptions = $customer->subscriptions()->paginate(15);

        return response()->json($subscriptions);
    }

    /**
     * Block cross-tenant access: a customer not owned by the caller's current
     * team is treated as not found (avoids leaking existence).
     */
    private function assertSameTeam(Request $request, Customer $customer): void
    {
        abort_unless($customer->team_id === $this->currentTeamId($request), 404);
    }

    private function currentTeamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
