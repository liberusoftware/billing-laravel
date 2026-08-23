

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Clients') }}
            </h2>
            <a href="{{ route('clients.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Client
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <!-- Search and Filter -->
                <form method="GET" class="mb-6">
                    <div class="flex gap-4">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="border rounded px-4 py-2 w-full" 
                               placeholder="Search clients...">
                        <select name="status" class="border rounded px-4 py-2">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Search
                        </button>
                    </div>
                </form>

                <!-- Clients Table -->
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 border-b">Name</th>
                            <th class="px-6 py-3 border-b">Email</th>
                            <th class="px-6 py-3 border-b">Company</th>
                            <th class="px-6 py-3 border-b">Status</th>
                            <th class="px-6 py-3 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                            <tr>
                                <td class="px-6 py-4 border-b">{{ $client->name }}</td>
                                <td class="px-6 py-4 border-b">{{ $client->email }}</td>
                                <td class="px-6 py-4 border-b">{{ $client->company }}</td>
                                <td class="px-6 py-4 border-b">
                                    <span class="px-2 py-1 rounded {{ $client->status === 'active' ? 'bg-green-200' : 'bg-red-200' }}">
                                        {{ $client->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 border-b">
                                    <a href="{{ route('clients.edit', $client) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                    <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 ml-4" 
                                                onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $clients->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>