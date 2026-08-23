<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallRateRule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CallRateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(CallRateRule::query()->where('team_id', $this->teamId($request))->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $data['team_id'] = $this->teamId($request);

        return response()->json(CallRateRule::query()->create($data), Response::HTTP_CREATED);
    }

    public function update(Request $request, int $callRate): JsonResponse
    {
        $rate = $this->find($request, $callRate);
        $rate->update($request->validate($this->rules(true)));

        return response()->json($rate->refresh());
    }

    public function destroy(Request $request, int $callRate): Response
    {
        $this->find($request, $callRate)->delete();

        return response()->noContent();
    }

    private function rules(bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'destination_prefix' => [$required, 'string', 'max:50'],
            'connection_fee' => 'sometimes|numeric|min:0',
            'rate_per_minute' => [$required, 'numeric', 'min:0'],
            'billing_increment_seconds' => 'sometimes|integer|min:1|max:3600',
            'currency' => 'sometimes|string|size:3',
            'is_active' => 'sometimes|boolean',
        ];
    }

    private function find(Request $request, int $id): CallRateRule
    {
        return CallRateRule::query()->where('team_id', $this->teamId($request))->findOrFail($id);
    }

    private function teamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
