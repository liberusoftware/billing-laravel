<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProjectBillingService
{
    /**
     * Convert the given logged time entries into a customer invoice for the
     * project. Only billable, not-yet-invoiced entries are billed; each is
     * stamped `invoiced_at` in the same transaction so it can never be billed
     * twice.
     *
     * @param  array<int, int>  $timeEntryIds
     */
    public function invoiceTime(Project $project, array $timeEntryIds): Invoice
    {
        return DB::transaction(function () use ($project, $timeEntryIds): Invoice {
            $entries = TimeEntry::query()
                ->whereIn('id', $timeEntryIds)
                ->billable()
                ->uninvoiced()
                ->get();

            if ($entries->isEmpty()) {
                throw new RuntimeException('No billable, uninvoiced time entries to invoice.');
            }

            $items = $entries->map(function (TimeEntry $entry): array {
                $hours = round($entry->duration_seconds / 3600, 2);
                // ponytail: no per-project rate column exists; use the entry rate, else 0.
                $rate = (float) ($entry->rate ?? 0);
                $lineTotal = round($rate * $hours, 2);

                return [
                    // ponytail: invoice_items.quantity is an integer column, so bill one
                    // line per entry (qty 1) and carry the hours in the description.
                    'description' => sprintf('%s — %.2f h', $entry->task->title, $hours),
                    'quantity' => 1,
                    'unit_price' => $lineTotal,
                    'total_price' => $lineTotal,
                    'currency' => 'USD',
                    'product_service_id' => null,
                ];
            })->all();

            $total = array_sum(array_column($items, 'total_price'));

            $invoice = Invoice::create([
                'customer_id' => $project->customer_id,
                'project_id' => $project->id,
                'issue_date' => now(),
                'due_date' => now()->addDays(14),
                'total_amount' => $total,
                'currency' => 'USD',
                'status' => 'pending',
            ]);

            $invoice->forceFill(['team_id' => $project->team_id])->save();
            $invoice->items()->createMany($items);

            TimeEntry::whereIn('id', $entries->pluck('id'))
                ->update(['invoiced_at' => now()]);

            return $invoice;
        });
    }
}
