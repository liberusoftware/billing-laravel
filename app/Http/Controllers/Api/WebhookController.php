<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use App\Services\WebhookService;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookService $webhookService
    ) {}

    /**
     * Get all webhook endpoints
     */
    public function index(Request $request)
    {
        $teamId = $request->user()?->current_team_id;

        $endpoints = WebhookEndpoint::query()
            ->when(
                $teamId,
                fn ($q) => $q->where(
                    'team_id',
                    $teamId
                )
            )
            ->with('webhookEvents')
            ->paginate($request->per_page ?? 15);

        return response()->json($endpoints);
    }

    /**
     * Validation rules for the endpoint url: valid https + not an internal/SSRF target.
     *
     * @return array<int, mixed>
     */
    private function urlValidationRules(bool $sometimes = false): array
    {
        $rules = [
            'required',
            'url:https',
            function (string $attribute, mixed $value, Closure $fail): void {
                try {
                    WebhookService::assertSafeUrl((string) $value);
                } catch (Exception $e) {
                    $fail($e->getMessage());
                }
            },
        ];

        return $sometimes ? ['sometimes', ...$rules] : $rules;
    }

    /**
     * Create a webhook endpoint
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'url' => $this->urlValidationRules(),
                'secret' => 'nullable|string',
                'events' => 'nullable|array',
                'description' => 'nullable|string',
                'max_retries' => 'nullable|integer|min:0|max:10',
                'retry_interval' => 'nullable|integer|min:10|max:3600',
            ]
        );

        $validated['team_id'] = $request->user()?->current_team_id;
        $validated['is_active'] = true;

        $endpoint = WebhookEndpoint::create($validated);

        return response()->json(
            [
                'data' => $endpoint,
                'message' => 'Webhook endpoint created successfully',
            ],
            201
        );
    }

    /**
     * Show a webhook endpoint
     */
    public function show(WebhookEndpoint $webhook): JsonResponse
    {
        $this->authorize(
            'view',
            $webhook
        );

        return response()->json(
            [
                'data' => $webhook->load('webhookEvents'),
            ]
        );
    }

    /**
     * Update a webhook endpoint
     */
    public function update(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $this->authorize(
            'update',
            $webhook
        );

        $validated = $request->validate(
            [
                'url' => $this->urlValidationRules(sometimes: true),
                'secret' => 'nullable|string',
                'events' => 'nullable|array',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
                'max_retries' => 'nullable|integer|min:0|max:10',
                'retry_interval' => 'nullable|integer|min:10|max:3600',
            ]
        );

        $webhook->update($validated);

        return response()->json(
            [
                'data' => $webhook,
                'message' => 'Webhook endpoint updated successfully',
            ]
        );
    }

    /**
     * Delete a webhook endpoint
     */
    public function destroy(WebhookEndpoint $webhook): JsonResponse
    {
        $this->authorize(
            'delete',
            $webhook
        );

        $webhook->delete();

        return response()->json(
            [
                'message' => 'Webhook endpoint deleted successfully',
            ]
        );
    }

    /**
     * Test a webhook endpoint
     */
    public function test(WebhookEndpoint $webhook): JsonResponse
    {
        $this->authorize(
            'update',
            $webhook
        );

        $testEvent = WebhookEvent::create(
            [
                'webhook_endpoint_id' => $webhook->id,
                'event_type' => 'test.event',
                'payload' => [
                    'test' => true,
                    'timestamp' => now()->toIso8601String(),
                ],
                'status' => 'pending',
            ]
        );

        $success = $this->webhookService->send($testEvent);

        return response()->json(
            [
                'success' => $success,
                'message' => $success ? 'Test webhook sent successfully' : 'Test webhook failed',
                'event' => $testEvent->fresh(),
            ]
        );
    }

    /**
     * Get webhook events
     */
    public function events(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $this->authorize(
            'view',
            $webhook
        );

        $events = $webhook->webhookEvents()
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return response()->json($events);
    }

    /**
     * Get available event types
     */
    public function eventTypes(): JsonResponse
    {
        return response()->json(
            [
                'data' => WebhookService::getAvailableEvents(),
            ]
        );
    }

    /**
     * Retry a failed webhook event
     */
    public function retryEvent(WebhookEvent $event): JsonResponse
    {
        $this->authorize(
            'update',
            $event->webhookEndpoint
        );

        if ($event->status === 'sent') {
            return response()->json(
                [
                    'message' => 'Event has already been sent successfully',
                ],
                400
            );
        }

        $success = $this->webhookService->send($event);

        return response()->json(
            [
                'success' => $success,
                'message' => $success ? 'Event resent successfully' : 'Event resend failed',
                'event' => $event->fresh(),
            ]
        );
    }
}
