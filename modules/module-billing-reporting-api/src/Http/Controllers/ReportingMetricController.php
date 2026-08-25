<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Reporting\Actions\RecordReportingMetric;
use Liberu\Billing\Reporting\Models\ReportingMetric;

final class ReportingMetricController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ReportingMetric::class);

        return response()->json(ReportingMetric::query()->where('team_id', $this->team($request))->when($request->filled('metric'), fn ($query) => $query->where('metric', $request->string('metric')))->latest('period_end')->paginate($request->integer('per_page', 25)));
    }

    public function store(Request $request, RecordReportingMetric $record): JsonResponse
    {
        Gate::authorize('create', ReportingMetric::class);
        $data = $request->validate(['metric' => ['required', 'in:mrr,arr,churn,aging,revenue,tax,usage,provisioning,collection,provider'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'value' => ['required', 'numeric'], 'currency' => ['nullable', 'string', 'size:3', 'alpha'], 'dimensions' => ['sometimes', 'array'], 'source' => ['nullable', 'string', 'max:100']]);

        return response()->json(['data' => $record->execute($this->team($request), $data)], 201);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }
}
