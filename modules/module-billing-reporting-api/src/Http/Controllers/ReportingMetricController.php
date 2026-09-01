<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Api\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

        return $this->paginated(ReportingMetric::query()->where('team_id', $this->team($request))->when($request->filled('metric'), fn ($query) => $query->where('metric', $request->string('metric')))->latest('period_end')->paginate($this->pageSize($request)));
    }

    public function store(Request $request, RecordReportingMetric $record): JsonResponse
    {
        Gate::authorize('create', ReportingMetric::class);
        $data = $request->validate(['metric' => ['required', 'in:mrr,arr,churn,aging,revenue,tax,usage,provisioning,collection,provider'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'value' => ['required', 'numeric'], 'currency' => ['nullable', 'string', 'size:3', 'alpha'], 'dimensions' => ['sometimes', 'array'], 'source' => ['nullable', 'string', 'max:100']]);

        return response()->json(['data' => $this->resource($record->execute($this->team($request), $data))], 201);
    }

    private function team(Request $request): int
    {
        return (int) (data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id'));
    }

    private function paginated(LengthAwarePaginator $results): JsonResponse
    {
        return response()->json(['data' => $results->getCollection()->map(fn (ReportingMetric $metric): array => $this->resource($metric))->values(), 'links' => ['next' => $results->nextPageUrl(), 'prev' => $results->previousPageUrl()], 'meta' => ['current_page' => $results->currentPage(), 'last_page' => $results->lastPage(), 'per_page' => $results->perPage(), 'total' => $results->total()]]);
    }

    private function pageSize(Request $request): int
    {
        return min(max((int) $request->input('page.size', $request->integer('per_page', 25)), 1), 100);
    }

    private function resource(ReportingMetric $metric): array
    {
        return ['id' => (string) $metric->getKey(), 'type' => 'billing-reporting-metric', 'attributes' => ['team_id' => $metric->team_id, 'metric' => $metric->metric->value, 'period_start' => $metric->period_start?->toDateString(), 'period_end' => $metric->period_end?->toDateString(), 'value' => $metric->value, 'currency' => $metric->currency, 'dimensions' => $metric->dimensions ?? [], 'source' => $metric->source, 'created_at' => $metric->created_at?->toISOString(), 'updated_at' => $metric->updated_at?->toISOString()]];
    }
}
