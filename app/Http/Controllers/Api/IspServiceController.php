<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BroadbandTechnology;
use App\Enums\RadiusPlatform;
use App\Http\Controllers\Controller;
use App\Models\IspService;
use App\Models\User;
use App\Services\IspServiceManager;
use App\Services\Radius\RadiusClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class IspServiceController extends Controller
{
    public function __construct(
        private readonly IspServiceManager $manager,
        private readonly RadiusClientFactory $radiusClients,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $services = $this->tenantQuery($request)
            ->with(['customer:id,name,email', 'productService:id,name'])
            ->when(
                $request->string('status')->isNotEmpty(),
                fn (Builder $query) => $query->where('status', $request->string('status')->toString())
            )
            ->when(
                $request->integer('customer_id') > 0,
                fn (Builder $query) => $query->where('customer_id', $request->integer('customer_id'))
            )
            ->paginate(25);

        return response()->json($services);
    }

    public function store(Request $request): JsonResponse
    {
        $teamId = $this->currentTeamId($request);
        $validated = $request->validate($this->rules($teamId));
        $validated['team_id'] = $teamId;

        $service = IspService::query()->create($validated);

        return response()->json($service->load('customer'), Response::HTTP_CREATED);
    }

    public function show(Request $request, int $ispService): JsonResponse
    {
        $service = $this->findForTenant($request, $ispService);

        return response()->json($service->load(['customer', 'productService', 'radiusSessions']));
    }

    public function update(Request $request, int $ispService): JsonResponse
    {
        $service = $this->findForTenant($request, $ispService);
        $service->update($request->validate($this->rules($this->currentTeamId($request), true, $service)));

        return response()->json($service->refresh());
    }

    public function destroy(Request $request, int $ispService): Response
    {
        $this->findForTenant($request, $ispService)->delete();

        return response()->noContent();
    }

    public function activate(Request $request, int $ispService): JsonResponse
    {
        $service = $this->findForTenant($request, $ispService);
        $service = $this->manager->activate($service, $this->radiusClients->make($service->radius_platform));

        return response()->json($service);
    }

    public function synchronize(Request $request, int $ispService): JsonResponse
    {
        $service = $this->findForTenant($request, $ispService);
        $service = $this->manager->synchronize($service, $this->radiusClients->make($service->radius_platform));

        return response()->json($service);
    }

    public function suspend(Request $request, int $ispService): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string|max:255']);
        $service = $this->findForTenant($request, $ispService);
        $service = $this->manager->suspend(
            $service,
            $this->radiusClients->make($service->radius_platform),
            $validated['reason']
        );

        return response()->json($service);
    }

    public function accounting(Request $request, int $ispService): JsonResponse
    {
        $service = $this->findForTenant($request, $ispService);
        $validated = $request->validate([
            'accounting_session_id' => 'required|string|max:255',
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'input_bytes' => 'sometimes|integer|min:0',
            'output_bytes' => 'sometimes|integer|min:0',
            'session_seconds' => 'sometimes|integer|min:0',
            'nas_identifier' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
        ]);
        $session = $this->manager->recordAccounting(
            $service,
            $validated,
            $this->radiusClients->make($service->radius_platform)
        );

        return response()->json($session, Response::HTTP_ACCEPTED);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?int $teamId, bool $updating = false, ?IspService $service = null): array
    {
        $presence = $updating ? 'sometimes' : 'required';

        return [
            'customer_id' => [
                $presence,
                'integer',
                Rule::exists('customers', 'id')->where('team_id', $teamId),
            ],
            'subscription_id' => [
                'nullable',
                'integer',
                Rule::exists('subscriptions', 'id')->where('team_id', $teamId),
            ],
            'product_service_id' => [
                'nullable',
                'integer',
                Rule::exists('products_services', 'id')->where('team_id', $teamId),
            ],
            'technology' => [$presence, Rule::enum(BroadbandTechnology::class)],
            'radius_platform' => [$presence, Rule::enum(RadiusPlatform::class)],
            'radius_username' => [
                $presence,
                'string',
                'max:255',
                Rule::unique('isp_services', 'radius_username')
                    ->where('team_id', $teamId)
                    ->ignore($service?->id),
            ],
            'radius_secret' => [$presence, 'string', 'min:12', 'max:255'],
            'download_limit_bps' => 'nullable|integer|min:1',
            'upload_limit_bps' => 'nullable|integer|min:1',
            'monthly_data_limit_bytes' => 'nullable|integer|min:1',
        ];
    }

    /**
     * @return Builder<IspService>
     */
    private function tenantQuery(Request $request): Builder
    {
        return IspService::query()->where('team_id', $this->currentTeamId($request));
    }

    private function findForTenant(Request $request, int $id): IspService
    {
        return $this->tenantQuery($request)->findOrFail($id);
    }

    private function currentTeamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
