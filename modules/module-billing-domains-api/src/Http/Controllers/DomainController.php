<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Domains\Actions\CreateDomain;
use Liberu\Billing\Domains\Actions\CreateDomainContact;
use Liberu\Billing\Domains\Actions\RedeemDomain;
use Liberu\Billing\Domains\Actions\RegisterDomain;
use Liberu\Billing\Domains\Actions\RenewDomain;
use Liberu\Billing\Domains\Actions\TransferDomain;
use Liberu\Billing\Domains\Actions\UpdateDomain;
use Liberu\Billing\Domains\Actions\UpsertDnsRecord;
use Liberu\Billing\Domains\Actions\UpsertDomainTld;
use Liberu\Billing\Domains\Models\DnsRecord;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Models\DomainContact;
use Liberu\Billing\Domains\Models\DomainTld;
use Liberu\Billing\Domains\Models\EppOperation;
use Liberu\Billing\Domains\Queries\SearchDomains;
use Liberu\Billing\Domains\Services\DomainPricingService;

final class DomainController extends Controller
{
    public function tlds(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Domain::class);

        return response()->json(['data' => DomainTld::query()->where('enabled', true)->orderBy('name')->get()]);
    }

    public function syncTlds(Request $request, DomainPricingService $pricing): JsonResponse
    {
        Gate::authorize('create', Domain::class);
        $data = $request->validate(['registrar' => ['required', 'string', 'max:50'], 'markup_value' => ['sometimes', 'numeric', 'min:0']]);

        return response()->json(['synchronized' => $pricing->syncTlds($data['registrar'], (float) ($data['markup_value'] ?? 10))]);
    }

    public function storeTld(Request $request, UpsertDomainTld $upsert): JsonResponse
    {
        Gate::authorize('create', Domain::class);
        $data = $request->validate(['name' => ['required', 'string', 'regex:/^\.?[A-Za-z0-9-]{2,63}$/'], 'registrar_cost' => ['nullable', 'numeric', 'min:0'], 'base_price' => ['required', 'numeric', 'min:0'], 'markup_type' => ['sometimes', 'in:none,percentage,fixed'], 'markup_value' => ['sometimes', 'numeric', 'min:0'], 'enabled' => ['sometimes', 'boolean']]);

        return response()->json(['data' => $upsert->execute($data)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Domain::class);
        $domains = Domain::query()->forTeam($this->teamId($request))->latest()->paginate($request->integer('per_page', 25));

        return response()->json(['data' => $domains->items(), 'meta' => ['current_page' => $domains->currentPage(), 'last_page' => $domains->lastPage()]]);
    }

    public function show(Request $request, Domain $domain): Domain
    {
        $domain = $this->forCurrentTeam($request, $domain);
        Gate::authorize('view', $domain);

        return $domain;
    }

    public function store(Request $request, CreateDomain $create): JsonResponse
    {
        Gate::authorize('create', Domain::class);
        $domain = $create->handle($this->teamId($request), $request->validate($this->rules()));

        return response()->json($domain, 201);
    }

    public function update(Request $request, Domain $domain, UpdateDomain $update): Domain
    {
        $domain = $this->forCurrentTeam($request, $domain);
        Gate::authorize('update', $domain);

        return $update->handle($domain, $request->validate($this->rules(false)));
    }

    public function destroy(Request $request, Domain $domain): JsonResponse
    {
        $domain = $this->forCurrentTeam($request, $domain);
        Gate::authorize('delete', $domain);
        $domain->delete();

        return response()->json(status: 204);
    }

    public function search(Request $request, SearchDomains $search): JsonResponse
    {
        Gate::authorize('viewAny', Domain::class);
        $data = $request->validate(['domain' => ['required', 'string', 'max:253'], 'registrar' => ['required', 'string', 'max:50']]);

        return response()->json($search->execute($data['domain'], $data['registrar']));
    }

    public function register(Request $request, Domain $domain, RegisterDomain $register): Domain
    {
        $domain = $this->forCurrentTeam($request, $domain);
        Gate::authorize('update', $domain);
        $data = $request->validate(['customer_id' => ['required']]);

        return $register->execute($domain, $data['customer_id']);
    }

    public function renew(Request $request, Domain $domain, RenewDomain $renew): Domain
    {
        $domain = $this->forCurrentTeam($request, $domain);
        Gate::authorize('update', $domain);

        return $renew->execute($domain, $request->integer('period', 1));
    }

    public function transfer(Request $request, Domain $domain, TransferDomain $transfer): Domain
    {
        $domain = $this->forCurrentTeam($request, $domain);
        Gate::authorize('update', $domain);
        $data = $request->validate(['auth_code' => ['required', 'string', 'max:255'], 'customer_id' => ['required'], 'registrar' => ['sometimes', 'nullable', 'string', 'max:50']]);

        return $transfer->execute($domain, $data['auth_code'], $data['customer_id'], $data['registrar'] ?? null);
    }

    public function contacts(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DomainContact::class);

        return response()->json(DomainContact::query()->where('team_id', $this->teamId($request))->latest()->paginate($request->integer('per_page', 25)));
    }

    public function eppOperations(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', EppOperation::class);
        $operations = EppOperation::query()->where('team_id', $this->teamId($request))->latest()->paginate($request->integer('per_page', 25));

        return response()->json(['data' => $operations->items(), 'meta' => ['current_page' => $operations->currentPage(), 'last_page' => $operations->lastPage()]]);
    }

    public function storeContact(Request $request, CreateDomainContact $create): JsonResponse
    {
        Gate::authorize('create', DomainContact::class);
        $data = $request->validate(['handle' => ['required', 'string', 'max:64'], 'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'details' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->execute($this->teamId($request), $data)], 201);
    }

    public function dns(Request $request, Domain $domain): JsonResponse
    {
        $domain = $this->forCurrentTeam($request, $domain);
        Gate::authorize('view', $domain);

        return response()->json(DnsRecord::query()->where('team_id', $this->teamId($request))->where('domain_id', $domain->id)->latest()->get());
    }

    public function storeDns(Request $request, Domain $domain, UpsertDnsRecord $upsert): JsonResponse
    {
        $domain = $this->forCurrentTeam($request, $domain);
        Gate::authorize('update', $domain);
        $data = $request->validate(['type' => ['required', 'string'], 'host' => ['required', 'string', 'max:255'], 'value' => ['required', 'string'], 'ttl' => ['sometimes', 'integer', 'min:60']]);

        return response()->json(['data' => $upsert->execute($this->teamId($request), [...$data, 'domain_id' => $domain->id])], 201);
    }

    public function redeem(Request $request, Domain $domain, RedeemDomain $redeem): Domain
    {
        $domain = $this->forCurrentTeam($request, $domain);
        Gate::authorize('update', $domain);

        return $redeem->execute($domain);
    }

    /** @return array<string,array<int,string>> */
    private function rules(bool $required = true): array
    {
        return [
            'name' => [$required ? 'required' : 'sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:50'],
            'registrar' => ['sometimes', 'nullable', 'string', 'max:100'],
            'transfer_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'registered_at' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }

    private function teamId(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }

    private function forCurrentTeam(Request $request, Domain $domain): Domain
    {
        return Domain::query()->forTeam($this->teamId($request))->whereKey($domain->getKey())->firstOrFail();
    }
}
