<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketEscalationRule;
use Illuminate\Support\Facades\Log;

class TicketEscalationService
{
    private const PRIORITY_LADDER = ['low' => 'medium', 'medium' => 'high'];

    /**
     * Apply every active escalation rule to the tickets that breach it.
     *
     * @return int Number of tickets escalated.
     */
    public function escalate(): int
    {
        $escalated = 0;

        foreach (TicketEscalationRule::query()->active()->get() as $rule) {
            foreach ($this->breachingTickets($rule) as $ticket) {
                if ($this->applyAction($rule, $ticket)) {
                    $escalated++;
                }
            }
        }

        return $escalated;
    }

    /**
     * Open tickets whose last activity (latest response, else creation) is
     * older than the rule's threshold, scoped to the rule's department.
     *
     * @return \Illuminate\Support\Collection<int, Ticket>
     */
    private function breachingTickets(TicketEscalationRule $rule): \Illuminate\Support\Collection
    {
        $threshold = now()->subMinutes($rule->minutes_without_response);

        return Ticket::query()
            ->where('status', 'open')
            ->when($rule->department_id !== null, fn ($query) => $query->where('department_id', $rule->department_id))
            ->withMax('responses', 'created_at')
            ->get()
            ->filter(function (Ticket $ticket) use ($threshold): bool {
                $lastActivity = $ticket->responses_max_created_at ?? $ticket->created_at;

                return $lastActivity !== null && $lastActivity < $threshold;
            });
    }

    private function applyAction(TicketEscalationRule $rule, Ticket $ticket): bool
    {
        return match ($rule->action) {
            'raise_priority' => $this->raisePriority($ticket),
            'reassign' => $this->reassign($rule, $ticket),
            'notify' => $this->notify($ticket),
            default => false,
        };
    }

    private function raisePriority(Ticket $ticket): bool
    {
        $next = self::PRIORITY_LADDER[$ticket->priority] ?? null;

        if ($next === null) {
            return false;
        }

        $ticket->update(['priority' => $next]);

        return true;
    }

    private function reassign(TicketEscalationRule $rule, Ticket $ticket): bool
    {
        if ($rule->target_user_id === null || $ticket->assigned_to === $rule->target_user_id) {
            return false;
        }

        $ticket->update(['assigned_to' => $rule->target_user_id]);

        return true;
    }

    private function notify(Ticket $ticket): bool
    {
        // ponytail: log-only; wire a real notification/mailable when staff alerting lands.
        Log::info('Ticket escalation: notify', ['ticket_id' => $ticket->id]);

        return true;
    }
}
