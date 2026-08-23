<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\TicketResponse;
use App\Models\User;

class InboundEmailService
{
    /**
     * Process a normalized inbound email payload: append to an existing
     * ticket the sender owns, or open a new one for the matched department.
     *
     * @param  array{from?: string, to?: string, subject?: string, body?: string, ticket_id?: int|string}  $payload
     */
    public function handle(array $payload): TicketResponse|Ticket
    {
        $sender = $this->resolveSender($payload['from'] ?? null);
        $subject = $payload['subject'] ?? '';
        $body = (string) ($payload['body'] ?? '');

        if ($sender !== null) {
            $ticket = $this->resolveTicket($payload, $subject);

            if ($ticket !== null && $ticket->user_id === $sender->id) {
                return TicketResponse::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $sender->id,
                    'message' => $body,
                ]);
            }
        }

        $department = $this->resolveDepartment($payload['to'] ?? null);

        return Ticket::create([
            'user_id' => $sender?->id,
            'department_id' => $department?->id,
            'title' => $subject !== '' ? $subject : 'Inbound email',
            'description' => $body,
            'status' => 'open',
            'priority' => 'medium',
        ]);
    }

    private function resolveSender(?string $from): ?User
    {
        if ($from === null || $from === '') {
            return null;
        }

        $user = User::where('email', $from)->first();

        if ($user !== null) {
            return $user;
        }

        return Customer::where('email', $from)->first()?->user;
    }

    /**
     * @param  array{ticket_id?: int|string}  $payload
     */
    private function resolveTicket(array $payload, string $subject): ?Ticket
    {
        $ticketId = $payload['ticket_id'] ?? null;

        if ($ticketId === null && preg_match('/\[Ticket #(\d+)\]/i', $subject, $matches) === 1) {
            $ticketId = $matches[1];
        }

        if ($ticketId === null) {
            return null;
        }

        return Ticket::find((int) $ticketId);
    }

    private function resolveDepartment(?string $to): ?TicketDepartment
    {
        if ($to !== null && $to !== '') {
            $department = TicketDepartment::where('email', $to)->first();

            if ($department !== null) {
                return $department;
            }
        }

        return TicketDepartment::active()->first();
    }
}
