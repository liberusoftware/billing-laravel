<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ResellerAgreement;
use App\Models\ResellerRevenueTransaction;
use App\Models\ResellerServiceDelegation;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use App\Services\ResellerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ResellerController extends Controller
{
    public function __construct(private readonly ResellerService $resellers) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->agreements($request)->with([
            'resellerTeam:id,name,slug,organisation_type',
        ])->withCount('delegations')->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $provider = Team::query()->findOrFail($this->teamId($request));
        $data = $request->validate([
            'reseller_team_id' => [
                'required',
                Rule::exists('teams', 'id')->where('parent_team_id', $provider->id),
                Rule::unique('reseller_agreements', 'reseller_team_id')->where('provider_team_id', $provider->id),
            ],
            'default_discount_percent' => 'sometimes|numeric|min:0|max:100',
            'revenue_share_percent' => 'sometimes|numeric|min:0|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'product_pricing' => 'nullable|array',
            'product_pricing.*' => 'array',
            'product_pricing.*.price' => 'nullable|numeric|min:0',
            'product_pricing.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        $reseller = Team::query()->findOrFail($data['reseller_team_id']);
        unset($data['reseller_team_id']);

        return response()->json(
            $this->resellers->createAgreement($provider, $reseller, $data),
            Response::HTTP_CREATED
        );
    }

    public function show(Request $request, int $agreement): JsonResponse
    {
        return response()->json($this->agreement($request, $agreement)->load([
            'resellerTeam', 'delegations.subscription', 'revenueTransactions',
        ]));
    }

    public function delegate(Request $request, int $agreement): JsonResponse
    {
        $model = $this->agreement($request, $agreement);
        $data = $request->validate([
            'subscription_id' => [
                'required',
                Rule::exists('subscriptions', 'id')->where('team_id', $this->teamId($request)),
                'unique:reseller_service_delegations,subscription_id',
            ],
            'retail_price' => 'nullable|numeric|min:0',
        ]);
        $subscription = Subscription::query()->findOrFail($data['subscription_id']);

        return response()->json(
            $this->resellers->delegate($model, $subscription, isset($data['retail_price']) ? (float) $data['retail_price'] : null),
            Response::HTTP_CREATED
        );
    }

    public function recordRevenue(Request $request, int $delegation): JsonResponse
    {
        $model = ResellerServiceDelegation::query()
            ->whereHas('agreement', fn (Builder $query) => $query->where('provider_team_id', $this->teamId($request)))
            ->findOrFail($delegation);
        $data = $request->validate([
            'gross_amount' => 'required|numeric|min:0',
            'invoice_id' => [
                'nullable',
                Rule::exists('invoices', 'id')->where('team_id', $this->teamId($request)),
            ],
        ]);
        $invoice = isset($data['invoice_id']) ? Invoice::query()->findOrFail($data['invoice_id']) : null;

        return response()->json(
            $this->resellers->recordRevenue($model, (float) $data['gross_amount'], $invoice),
            Response::HTTP_CREATED
        );
    }

    public function settle(Request $request, int $transaction): JsonResponse
    {
        $model = ResellerRevenueTransaction::query()
            ->whereHas('agreement', fn (Builder $query) => $query->where('provider_team_id', $this->teamId($request)))
            ->findOrFail($transaction);

        return response()->json($this->resellers->settle($model));
    }

    /** @return Builder<ResellerAgreement> */
    private function agreements(Request $request): Builder
    {
        return ResellerAgreement::query()->where('provider_team_id', $this->teamId($request));
    }

    private function agreement(Request $request, int $id): ResellerAgreement
    {
        return $this->agreements($request)->findOrFail($id);
    }

    private function teamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
