<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Api\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Reporting\Actions\CalculateReportingMetric;
use Liberu\Billing\Reporting\Actions\CreateMetricSnapshot;
use Liberu\Billing\Reporting\Actions\ExportReportingMetrics;
use Liberu\Billing\Reporting\Actions\GenerateCustomerBillingSummary;
use Liberu\Billing\Reporting\Actions\RecordReportingMetric;
use Liberu\Billing\Reporting\Models\MetricSnapshot;
use Liberu\Billing\Reporting\Models\ReportingMetric;
use Liberu\Billing\Reporting\Queries\ListMetricSnapshots;
use Liberu\Billing\Reporting\Queries\ListReportingMetrics;

final class ReportingController extends Controller
{
    public function metrics(Request $request, ListReportingMetrics $metrics): JsonResponse
    {
        Gate::authorize('viewAny', ReportingMetric::class);
        $results = $metrics->execute($this->team($request), $request->string('metric')->toString() ?: null, $request->integer('per_page', 25));

        return response()->json($this->collection($results));
    }

    public function exportMetrics(Request $request, ExportReportingMetrics $export): Response
    {
        Gate::authorize('viewAny', ReportingMetric::class);

        return response($export->execute($this->team($request), $request->string('metric')->toString() ?: null), 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="billing-reporting-metrics.csv"']);
    }

    public function recordMetric(Request $request, RecordReportingMetric $record): JsonResponse
    {
        Gate::authorize('create', ReportingMetric::class);
        $data = $request->validate($this->metricRules());
        $metric = $record->execute($this->team($request), $data);

        return response()->json(['data' => $this->metric($metric)], 201);
    }

    public function calculateMetric(Request $request, CalculateReportingMetric $calculate, RecordReportingMetric $record): JsonResponse
    {
        Gate::authorize('create', ReportingMetric::class);
        $data = $request->validate(['metric' => ['required', 'in:mrr,arr,churn,aging,revenue,tax,usage,provisioning,collection,provider'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'currency' => ['nullable', 'string', 'size:3', 'alpha']]);
        $calculated = $calculate->execute($this->team($request), $data['metric'], CarbonImmutable::parse($data['period_start']), CarbonImmutable::parse($data['period_end']), $data['currency'] ?? null);

        return response()->json(['data' => $this->metric($record->execute($this->team($request), $calculated))], 201);
    }

    public function snapshots(Request $request, ListMetricSnapshots $snapshots): JsonResponse
    {
        Gate::authorize('viewAny', MetricSnapshot::class);

        return response()->json($this->collection($snapshots->execute($this->team($request), $request->integer('per_page', 25))));
    }

    public function customerSummary(Request $request, GenerateCustomerBillingSummary $summary, int $customer): JsonResponse
    {
        Gate::authorize('viewAny', ReportingMetric::class);
        $data = $request->validate(['currency' => ['nullable', 'string', 'size:3', 'alpha']]);

        return response()->json(['data' => $summary->execute($this->team($request), $customer, $data['currency'] ?? null)]);
    }

    public function createSnapshot(Request $request, CreateMetricSnapshot $create): JsonResponse
    {
        Gate::authorize('create', MetricSnapshot::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'status' => ['sometimes', 'string', 'in:active,ready,failed'], 'metadata' => ['sometimes', 'array']]);

        return response()->json(['data' => $create->handle($this->team($request), $data)], 201);
    }

    public function snapshot(Request $request, int $record): JsonResponse
    {
        Gate::authorize('viewAny', MetricSnapshot::class);
        $snapshot = MetricSnapshot::query()->forTeam($this->team($request))->findOrFail($record);
        Gate::authorize('view', $snapshot);

        return response()->json(['data' => $snapshot]);
    }

    private function team(Request $request): int
    {
        $team = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }

    /** @return array<string, mixed> */
    private function metricRules(): array
    {
        return ['metric' => ['required', 'in:mrr,arr,churn,aging,revenue,tax,usage,provisioning,collection,provider'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'value' => ['required', 'numeric'], 'currency' => ['nullable', 'string', 'size:3', 'alpha'], 'dimensions' => ['sometimes', 'array'], 'source' => ['nullable', 'string', 'max:100']];
    }

    /** @return array<string, mixed> */
    private function collection($results): array
    {
        return ['data' => $results->getCollection()->values(), 'links' => ['next' => $results->nextPageUrl(), 'prev' => $results->previousPageUrl()], 'meta' => ['current_page' => $results->currentPage(), 'last_page' => $results->lastPage(), 'per_page' => $results->perPage(), 'total' => $results->total()]];
    }

    /** @return array<string, mixed> */
    private function metric(ReportingMetric $metric): array
    {
        return ['id' => (string) $metric->getKey(), 'type' => 'billing-reporting-metric', 'attributes' => ['metric' => $metric->metric->value, 'period_start' => $metric->period_start?->toDateString(), 'period_end' => $metric->period_end?->toDateString(), 'value' => $metric->value, 'currency' => $metric->currency, 'dimensions' => $metric->dimensions ?? [], 'source' => $metric->source]];
    }
}
