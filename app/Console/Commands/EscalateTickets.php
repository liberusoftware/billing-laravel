<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TicketEscalationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Apply ticket escalation rules to overdue tickets')]
#[Signature('tickets:escalate')]
class EscalateTickets extends Command
{
    public function handle(TicketEscalationService $service): int
    {
        $escalated = $service->escalate();

        $this->info("Escalated {$escalated} tickets");

        return Command::SUCCESS;
    }
}
