<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): Factory|View
    {
        $query = Client::query();

        if ($request->search) {
            $query->where(
                function ($q) use ($request): void {
                    $q->where(
                        'name',
                        'like',
                        "%{$request->search}%"
                    )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$request->search}%"
                        )
                        ->orWhere(
                            'company',
                            'like',
                            "%{$request->search}%"
                        );
                }
            );
        }

        if ($request->status) {
            $query->where(
                'status',
                $request->status
            );
        }

        $clients = $query->paginate(10);

        return view(
            'clients.index',
            compact('clients')
        );
    }

    public function create(): Factory|View
    {
        return view('clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:clients',
                'phone' => 'nullable|string|max:20',
                'company' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'notes' => 'nullable|string',
                'status' => 'required|in:active,inactive',
            ]
        );

        Client::create($validated);

        return redirect()->route('clients.index')->with(
            'success',
            'Client created successfully'
        );
    }

    public function edit(Client $client): Factory|View
    {
        return view(
            'clients.edit',
            compact('client')
        );
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:clients,email,'.$client->id,
                'phone' => 'nullable|string|max:20',
                'company' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'notes' => 'nullable|string',
                'status' => 'required|in:active,inactive',
            ]
        );

        $client->update($validated);

        return redirect()->route('clients.index')->with(
            'success',
            'Client updated successfully'
        );
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')->with(
            'success',
            'Client deleted successfully'
        );
    }
}
