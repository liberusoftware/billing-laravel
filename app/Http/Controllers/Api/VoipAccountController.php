<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\VoipPlatform;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VoipAccount;
use App\Services\Voip\VoipPlatformClientFactory;
use App\Services\VoipBillingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class VoipAccountController extends Controller
{
    public function __construct(
        private readonly VoipBillingService $billing,
        private readonly VoipPlatformClientFactory $clients,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->tenantQuery($request)->with('customer:id,name,email')->paginate(25));
    }

    public function store(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        $data = $request->validate($this->rules($teamId));
        $data['team_id'] = $teamId;

        return response()->json(VoipAccount::query()->create($data), Response::HTTP_CREATED);
    }

    public function show(Request $request, int $voipAccount): JsonResponse
    {
        return response()->json($this->find($request, $voipAccount)->load([
            'customer', 'didNumbers', 'callDetailRecords', 'fraudAlerts',
        ]));
    }

    public function update(Request $request, int $voipAccount): JsonResponse
    {
        $account = $this->find($request, $voipAccount);
        $account->update($request->validate($this->rules($this->teamId($request), true, $account)));

        return response()->json($account->refresh());
    }

    public function destroy(Request $request, int $voipAccount): Response
    {
        $this->find($request, $voipAccount)->delete();

        return response()->noContent();
    }

    public function provision(Request $request, int $voipAccount): JsonResponse
    {
        $account = $this->find($request, $voipAccount);

        return response()->json($this->billing->provision($account, $this->clients->make($account->platform)));
    }

    public function ingestCdr(Request $request, int $voipAccount): JsonResponse
    {
        $account = $this->find($request, $voipAccount);
        $data = $request->validate([
            'external_id' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'direction' => 'sometimes|in:inbound,outbound',
            'started_at' => 'required|date',
            'answered_at' => 'nullable|date|after_or_equal:started_at',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'duration_seconds' => 'sometimes|integer|min:0',
            'disposition' => 'sometimes|string|max:50',
            'metadata' => 'nullable|array',
        ]);

        return response()->json($this->billing->ingestCdr($account, $data), Response::HTTP_ACCEPTED);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?int $teamId, bool $update = false, ?VoipAccount $account = null): array
    {
        $required = $update ? 'sometimes' : 'required';

        return [
            'customer_id' => [$required, Rule::exists('customers', 'id')->where('team_id', $teamId)],
            'subscription_id' => ['nullable', Rule::exists('subscriptions', 'id')->where('team_id', $teamId)],
            'platform' => [$required, Rule::enum(VoipPlatform::class)],
            'sip_username' => [
                $required, 'string', 'max:255',
                Rule::unique('voip_accounts', 'sip_username')->where('team_id', $teamId)->ignore($account?->id),
            ],
            'sip_secret' => [$required, 'string', 'min:12', 'max:255'],
            'caller_id' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'max_concurrent_calls' => 'sometimes|integer|min:1|max:1000',
            'international_enabled' => 'sometimes|boolean',
        ];
    }

    /** @return Builder<VoipAccount> */
    private function tenantQuery(Request $request): Builder
    {
        return VoipAccount::query()->where('team_id', $this->teamId($request));
    }

    private function find(Request $request, int $id): VoipAccount
    {
        return $this->tenantQuery($request)->findOrFail($id);
    }

    private function teamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
