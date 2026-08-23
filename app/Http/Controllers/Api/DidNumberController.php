<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DidNumber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class DidNumberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            DidNumber::query()->where('team_id', $this->teamId($request))->with('voipAccount:id,sip_username')->paginate(50)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        $data = $request->validate([
            'number' => [
                'required', 'string', 'max:50',
                Rule::unique('did_numbers', 'number')->where('team_id', $teamId),
            ],
            'country_code' => 'required|string|size:2',
            'monthly_price' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
        ]);
        $data['team_id'] = $teamId;

        return response()->json(DidNumber::query()->create($data), Response::HTTP_CREATED);
    }

    public function assign(Request $request, int $didNumber): JsonResponse
    {
        $teamId = $this->teamId($request);
        $data = $request->validate([
            'voip_account_id' => ['required', Rule::exists('voip_accounts', 'id')->where('team_id', $teamId)],
        ]);
        $did = DidNumber::query()->where('team_id', $teamId)->findOrFail($didNumber);
        $did->update(['voip_account_id' => $data['voip_account_id'], 'status' => 'assigned']);

        return response()->json($did->refresh());
    }

    public function destroy(Request $request, int $didNumber): Response
    {
        DidNumber::query()->where('team_id', $this->teamId($request))->findOrFail($didNumber)->delete();

        return response()->noContent();
    }

    private function teamId(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user?->current_team_id;
    }
}
