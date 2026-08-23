

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Services') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                @foreach($subscriptions as $subscription)
                    <div class="border-b pb-4 mb-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold">
                                    {{ $subscription->productService->name }}
                                </h3>
                                <p class="text-gray-600">
                                    Status: {{ ucfirst($subscription->status) }}
                                </p>
                                <p class="text-gray-600">
                                    Expires: {{ $subscription->end_date->format('M d, Y') }}
                                </p>
                            </div>
                            <div>
                                <a href="{{ route('client.services.show', $subscription) }}" 
                                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Manage
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>