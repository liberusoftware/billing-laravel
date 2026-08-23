<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ticket #{{ $ticket->id }}: {{ $ticket->title }}
            </h2>
            <a href="{{ route('tickets.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Tickets</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Ticket Details --}}
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ match($ticket->priority) {
                                'high'   => 'bg-red-100 text-red-700',
                                'medium' => 'bg-yellow-100 text-yellow-700',
                                default  => 'bg-gray-100 text-gray-700',
                            } }} mr-2">
                            {{ ucfirst($ticket->priority) }} Priority
                        </span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                            {{ match($ticket->status) {
                                'open'        => 'bg-blue-100 text-blue-700',
                                'in_progress' => 'bg-yellow-100 text-yellow-700',
                                'closed'      => 'bg-green-100 text-green-700',
                                default       => 'bg-gray-100 text-gray-700',
                            } }}">
                            {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-400">{{ $ticket->created_at->format('M d, Y H:i') }}</span>
                </div>
                <p class="text-gray-700 whitespace-pre-line">{{ $ticket->description }}</p>
                <p class="mt-3 text-sm text-gray-400">Opened by {{ $ticket->user?->name }}</p>
            </div>

            {{-- Update Status (Admin) --}}
            @can('update', $ticket)
                <div class="bg-white shadow rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Update Status</h3>
                    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="flex items-center gap-3">
                        @csrf
                        @method('PUT')
                        <select name="status" class="border-gray-300 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700 transition-colors">
                            Save
                        </button>
                    </form>
                </div>

                <div class="bg-white shadow rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Assign</h3>
                    <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="flex items-center gap-3">
                        @csrf
                        <select name="assigned_to" class="border-gray-300 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Unassigned</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}" {{ $ticket->assigned_to === $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700 transition-colors">
                            Save
                        </button>
                    </form>
                    @if($ticket->department)
                        <p class="mt-3 text-xs text-gray-400">Department: {{ $ticket->department->name }}</p>
                    @endif
                </div>
            @endcan

            {{-- Project link (Admin) --}}
            @can('update', $ticket)
                <div class="bg-white shadow rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Project</h3>
                    @if($ticket->project)
                        <p class="text-sm text-gray-700">
                            Linked to project <span class="font-medium">{{ $ticket->project->name }}</span>.
                        </p>
                    @else
                        <form method="POST" action="{{ route('tickets.project.create', $ticket) }}" class="flex items-center gap-3">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-500 transition-colors">
                                Create project from ticket
                            </button>
                            <span class="text-xs text-gray-400">Uses the ticket's customer; errors if none is linked.</span>
                        </form>
                    @endif
                </div>
            @endcan

            {{-- Responses --}}
            @if($ticket->responses->isNotEmpty())
                <div class="space-y-4">
                    @foreach($ticket->responses as $response)
                        <div class="bg-white shadow rounded-xl p-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900">{{ $response->user?->name }}</span>
                                <span class="text-xs text-gray-400">{{ $response->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-gray-700 whitespace-pre-line text-sm">{{ $response->content }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
