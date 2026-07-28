<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\InfrastructureAssetType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InfrastructureAsset;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\IspService;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VoipAccount;
use App\Services\IpamService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class InfrastructureController extends Controller
{
    public function __construct(private readonly IpamService $ipam) {}

    public function assets(Request $request): JsonResponse
    {
        return response()->json(
            InfrastructureAsset::query()
                ->where('team_id', $this->teamId($request))
                ->withCount(['children', 'ipPools'])
                ->paginate(50)
        );
    }

    public function storeAsset(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        $data = $request->validate([
            'parent_id' => ['nullable', Rule::exists('infrastructure_assets', 'id')->where('team_id', $teamId)],
            'asset_type' => ['required', Rule::enum(InfrastructureAssetType::class)],
            'name' => 'required|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'serial_number' => [
                'nullable', 'string', 'max:255',
                Rule::unique('infrastructure_assets', 'serial_number')->where('team_id', $teamId),
            ],
            'vendor' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,maintenance,retired,failed',
            'metadata' => 'nullable|array',
        ]);
        $data['team_id'] = $teamId;

        return response()->json(InfrastructureAsset::query()->create($data), Response::HTTP_CREATED);
    }

    public function updateAsset(Request $request, int $asset): JsonResponse
    {
        $model = InfrastructureAsset::query()->where('team_id', $this->teamId($request))->findOrFail($asset);
        $model->update($request->validate([
            'parent_id' => [
                'nullable',
                Rule::exists('infrastructure_assets', 'id')->where('team_id', $this->teamId($request)),
                Rule::notIn([$model->id]),
            ],
            'asset_type' => ['sometimes', Rule::enum(InfrastructureAssetType::class)],
            'name' => 'sometimes|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'serial_number' => [
                'nullable', 'string', 'max:255',
                Rule::unique('infrastructure_assets', 'serial_number')
                    ->where('team_id', $this->teamId($request))->ignore($model->id),
            ],
            'vendor' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,maintenance,retired,failed',
            'metadata' => 'nullable|array',
        ]));

        return response()->json($model->refresh());
    }

    public function pools(Request $request): JsonResponse
    {
        return response()->json(
            IpPool::query()->where('team_id', $this->teamId($request))
                ->withCount(['addresses', 'addresses as assigned_count' => fn ($query) => $query->where('status', 'assigned')])
                ->paginate(50)
        );
    }

    public function storePool(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'cidr' => 'required|string|max:50',
            'infrastructure_asset_id' => [
                'nullable', Rule::exists('infrastructure_assets', 'id')->where('team_id', $teamId),
            ],
            'gateway' => 'nullable|ip',
            'vlan_id' => 'nullable|integer|min:1|max:4094',
        ]);

        return response()->json($this->ipam->createPool((int) $teamId, $data), Response::HTTP_CREATED);
    }

    public function addresses(Request $request, int $pool): JsonResponse
    {
        $ipPool = IpPool::query()->where('team_id', $this->teamId($request))->findOrFail($pool);

        return response()->json($ipPool->addresses()->paginate(100));
    }

    public function allocate(Request $request, int $pool): JsonResponse
    {
        $ipPool = IpPool::query()->where('team_id', $this->teamId($request))->findOrFail($pool);
        $data = $request->validate([
            'target_type' => 'required|in:asset,customer,subscription,isp_service,voip_account',
            'target_id' => 'required|integer',
            'hostname' => 'nullable|string|max:255',
        ]);
        $target = $this->assignmentTarget($request, $data['target_type'], (int) $data['target_id']);

        return response()->json(
            $this->ipam->allocate($ipPool, $target, $data['hostname'] ?? null),
            Response::HTTP_CREATED
        );
    }

    public function release(Request $request, int $address): JsonResponse
    {
        $model = IpAddress::query()->where('team_id', $this->teamId($request))->findOrFail($address);

        return response()->json($this->ipam->release($model));
    }

    private function assignmentTarget(Request $request, string $type, int $id): Model
    {
        $class = match ($type) {
            'asset' => InfrastructureAsset::class,
            'customer' => Customer::class,
            'subscription' => Subscription::class,
            'isp_service' => IspService::class,
            'voip_account' => VoipAccount::class,
            default => throw new InvalidArgumentException('Unsupported infrastructure assignment target.'),
        };

        return $class::query()->where('team_id', $this->teamId($request))->findOrFail($id);
    }

    private function teamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
