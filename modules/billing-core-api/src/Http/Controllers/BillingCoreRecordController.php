<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Core\Actions\ConvertCurrency;
use Liberu\Billing\Core\Actions\CreateBillingRecord;
use Liberu\Billing\Core\Actions\UpdateBillingRecord;
use Liberu\Billing\Core\Models\BillingContact;
use Liberu\Billing\Core\Models\BillingCurrency;
use Liberu\Billing\Core\Models\BillingSequence;
use Liberu\Billing\Core\Models\BillingSetting;
use Liberu\Billing\Core\Models\BillingTaxProfile;
use Liberu\Billing\Core\Models\BillingTerm;
use Liberu\Billing\Core\Queries\ListBillingRecords;

final class BillingCoreRecordController extends Controller
{
    public function index(Request $request, string $type, ListBillingRecords $list): JsonResponse
    {
        $model = $this->model($type);
        Gate::authorize('viewAny', $model);
        $records = $list->execute($model, $this->teamId($request), $request->integer('per_page', 25));

        return response()->json(['data' => $records->items(), 'links' => ['next' => $records->nextPageUrl(), 'prev' => $records->previousPageUrl()], 'meta' => ['current_page' => $records->currentPage(), 'last_page' => $records->lastPage()]]);
    }

    public function store(Request $request, string $type, CreateBillingRecord $create): JsonResponse
    {
        $model = $this->model($type);
        Gate::authorize('create', $model);
        $data = $request->validate($this->rules($type));
        $data['team_id'] = $this->teamId($request);

        return response()->json(['data' => $create->execute($model, $data)], 201);
    }

    public function convertCurrency(Request $request, ConvertCurrency $convert): JsonResponse
    {
        Gate::authorize('viewAny', BillingCurrency::class);
        $data = $request->validate(['amount' => ['required', 'numeric'], 'from' => ['required', 'string', 'size:3', 'alpha'], 'to' => ['required', 'string', 'size:3', 'alpha']]);

        return response()->json(['data' => $convert->execute($this->teamId($request), (float) $data['amount'], $data['from'], $data['to'])]);
    }

    public function update(Request $request, string $type, int $record, UpdateBillingRecord $update): JsonResponse
    {
        $model = $this->model($type);
        $instance = $this->forCurrentTeam($model, $record, $request);
        Gate::authorize('update', $instance);
        $data = $request->validate($this->rules($type, false));
        unset($data['team_id']);

        return response()->json(['data' => $update->execute($instance, $data)]);
    }

    public function destroy(Request $request, string $type, int $record): JsonResponse
    {
        $model = $this->model($type);
        $instance = $this->forCurrentTeam($model, $record, $request);
        Gate::authorize('delete', $instance);
        $instance->delete();

        return response()->json(status: 204);
    }

    /** @return class-string */
    private function model(string $type): string
    {
        return match ($type) {
            'contacts' => BillingContact::class,
            'currencies' => BillingCurrency::class,
            'tax-profiles' => BillingTaxProfile::class,
            'sequences' => BillingSequence::class,
            'terms' => BillingTerm::class,
            'settings' => BillingSetting::class,
            default => abort(404, 'Unknown Billing Core resource.'),
        };
    }

    /** @return array<string,array<int,string>> */
    private function rules(string $type, bool $required = true): array
    {
        $name = $required ? 'required' : 'sometimes';

        return match ($type) {
            'contacts' => ['name' => [$name, 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:50'], 'metadata' => ['sometimes', 'array']],
            'currencies' => ['code' => [$name, 'string', 'size:3', 'alpha'], 'name' => [$name, 'string', 'max:100'], 'decimal_places' => ['sometimes', 'integer', 'between:0,8'], 'enabled' => ['sometimes', 'boolean'], 'exchange_rate' => ['nullable', 'numeric', 'gt:0']],
            'tax-profiles' => ['name' => [$name, 'string', 'max:255'], 'rate' => [$name, 'numeric', 'between:0,100'], 'jurisdiction' => ['nullable', 'string', 'max:100'], 'inclusive' => ['sometimes', 'boolean'], 'enabled' => ['sometimes', 'boolean']],
            'sequences' => ['name' => [$name, 'string', 'max:100'], 'prefix' => ['nullable', 'string', 'max:30'], 'next_number' => ['sometimes', 'integer', 'min:1']],
            'terms' => ['name' => [$name, 'string', 'max:100'], 'due_days' => ['sometimes', 'integer', 'min:0', 'max:3650'], 'default' => ['sometimes', 'boolean']],
            'settings' => ['values' => [$required ? 'required' : 'sometimes', 'array']],
            default => [],
        };
    }

    private function teamId(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }

    private function forCurrentTeam(string $model, int $record, Request $request): object
    {
        $teamId = $this->teamId($request);

        return $model::query()->whereKey($record)->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId))->firstOrFail();
    }
}
