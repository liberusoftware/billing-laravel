<?php

namespace App\Http\Controllers;

use App\Actions\CreateProjectFromTicket;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCustomField;
use App\Models\User;
use App\Services\CannedResponseService;
use App\Notifications\NewTicketNotification;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(): Factory|View
    {
        $user = auth()->user();

        $tickets = $user->hasRole('super_admin') || $user->hasRole('admin')
            ? Ticket::with('user')->latest()->paginate(10)
            : $user->tickets()->latest()->paginate(10);

        return view(
            'tickets.index',
            compact('tickets')
        );
    }

    public function create(): Factory|View
    {
        $subscriptions = auth()->user()->customer?->subscriptions()->with('productService')->latest()->get() ?? collect();

        return view('tickets.create', compact('subscriptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
            ],
            'priority' => [
                'required',
                'in:low,medium,high',
            ],
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
            'domain_name' => ['nullable', 'string', 'max:255'],
        ];

        // Add a rule per admin-defined custom field; required ones must be filled.
        $customFields = TicketCustomField::active()->get();
        foreach ($customFields as $field) {
            $rules["custom_fields.{$field->id}"] = $field->is_required ? ['required'] : ['nullable'];
        }

        $validated = $request->validate($rules);

        if (isset($validated['subscription_id'])) {
            $owned = auth()->user()->customer?->subscriptions()->whereKey($validated['subscription_id'])->exists() ?? false;
            abort_unless($owned, 404);
        }

        $ticket = Ticket::create(
            [
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'custom_fields' => $request->input('custom_fields', []),
                'subscription_id' => $validated['subscription_id'] ?? null,
                'domain_name' => $validated['domain_name'] ?? null,
            ]
        );

        $admins = User::role(
            [
                'admin',
                'super_admin',
            ]
        )->get();
        Notification::send(
            $admins,
            new NewTicketNotification($ticket)
        );

        return redirect()->route(
            'tickets.show',
            $ticket
        )
            ->with(
                'success',
                'Ticket created successfully.'
            );
    }

    public function show(Ticket $ticket): Factory|View
    {
        $this->authorize(
            'view',
            $ticket
        );
        $ticket->load(
            [
                'responses.user',
                'user',
                'assignee',
                'department',
                'subscription',
            ]
        );

        $staff = User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'super_admin']))->get();
        $cannedResponses = app(CannedResponseService::class)->getAll($ticket->user?->currentTeam?->id);

        return view(
            'tickets.show',
            compact('ticket', 'staff', 'cannedResponses')
        );
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize(
            'update',
            $ticket
        );

        $validated = $request->validate(
            [
                'status' => [
                    'required',
                    'in:open,in_progress,closed',
                ],
            ]
        );

        $ticket->update($validated);

        return redirect()->back()->with(
            'success',
            'Ticket status updated.'
        );
    }

    public function createProject(
        Request $request,
        Ticket $ticket,
        CreateProjectFromTicket $action
    ): RedirectResponse {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        $customer = isset($validated['customer_id'])
            ? Customer::find($validated['customer_id'])
            : null;

        try {
            $project = $action($ticket, $customer);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['customer_id' => $e->getMessage()]);
        }

        return redirect()->back()->with(
            'success',
            "Project #{$project->id} created from ticket."
        );
    }

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize(
            'update',
            $ticket
        );

        $validated = $request->validate(
            [
                'assigned_to' => [
                    'nullable',
                    'exists:users,id',
                ],
            ]
        );

        $ticket->update(['assigned_to' => $validated['assigned_to'] ?? null]);

        return redirect()->back()->with(
            'success',
            'Ticket assignment updated.'
        );
    }

    public function downloadAttachment(TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize(
            'view',
            $attachment->owningTicket()
        );

        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->original_name
        );
    }
}
