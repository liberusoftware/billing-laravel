<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\InboundEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboundEmailController extends Controller
{
    public function __construct(
        protected InboundEmailService $inboundEmailService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'from' => ['required', 'email'],
            'to' => ['required', 'email'],
            'subject' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'ticket_id' => ['nullable', 'integer'],
        ]);

        $result = $this->inboundEmailService->handle($payload);

        return response()->json([
            'type' => $result instanceof Ticket ? 'ticket' : 'response',
            'id' => $result->id,
            'ticket_id' => $result instanceof Ticket ? $result->id : $result->ticket_id,
        ], 201);
    }
}
