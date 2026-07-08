<?php

namespace App\Http\Controllers;

use App\Models\ClientNote;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->currentTeamId($request);

        // Scope to notes whose client belongs to the caller's team.
        $query = ClientNote::with(['user'])
            ->whereHas('client', fn (Builder $q) => $q->where('team_id', $teamId))
            ->where(
                'client_id',
                $request->client_id
            );

        // Handle content search
        if ($request->has('content')) {
            $query->where(
                'content',
                'like',
                "%{$request->content}%"
            );
        }

        // Handle date range
        if ($request->has('dateRange')) {
            switch ($request->dateRange) {
                case 'today':
                    $query->whereDate(
                        'created_at',
                        Carbon::today()
                    );
                    break;
                case 'week':
                    $query->whereBetween(
                        'created_at',
                        [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek(),
                        ]
                    );
                    break;
                case 'month':
                    $query->whereBetween(
                        'created_at',
                        [
                            Carbon::now()->startOfMonth(),
                            Carbon::now()->endOfMonth(),
                        ]
                    );
                    break;
                case 'custom':
                    if ($request->startDate && $request->endDate) {
                        $query->whereBetween(
                            'created_at',
                            [
                                $request->startDate,
                                $request->endDate,
                            ]
                        );
                    }
                    break;
            }
        }

        $notes = $query->latest()->paginate(10);

        return response()->json($notes);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->input('query');

        $suggestions = ClientNote::where(
            'content',
            'like',
            "%{$query}%"
        )
            ->select('content')
            ->distinct()
            ->limit(5)
            ->get();

        return response()->json($suggestions);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(
            [
                'client_id' => ['required', Rule::exists('clients', 'id')->where('team_id', $this->currentTeamId($request))],
                'content' => 'required|string',
            ]
        );

        $note = ClientNote::create(
            [
                'client_id' => $request->client_id,
                'user_id' => $request->user()?->getAuthIdentifier(),
                'content' => $request->content,
            ]
        );

        return response()->json($note->load('user'));
    }

    public function destroy(Request $request, ClientNote $note): JsonResponse
    {
        // 404 unless the note's client belongs to the caller's team.
        abort_unless($note->client?->team_id === $this->currentTeamId($request), 404);

        $note->delete();

        return response()->json(['message' => 'Note deleted successfully']);
    }

    private function currentTeamId(Request $request): ?int
    {
        return $request->user()?->current_team_id;
    }
}
