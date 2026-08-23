

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Service') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">
                    {{ $subscription->productService->name }}
                </h3>

                @if($subscription->scheduled_change)
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                        Scheduled change: {{ ucfirst($subscription->scheduled_change['type']) }}
                        effective {{ Carbon\Carbon::parse($subscription->scheduled_change['effective_date'])->format('M d, Y') }}
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-6">
                    <!-- Upgrade Options -->
                    <div>
                        <h4 class="font-semibold mb-2">Upgrade Options</h4>
                        @foreach($availableUpgrades as $upgrade)
                            <div class="border p-4 mb-2">
                                <h5>{{ $upgrade->name }}</h5>
                                <p>Price: {{ $upgrade->price }} {{ $upgrade->currency }}</p>
                                <form action="{{ route('client.services.upgrade', $subscription) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="new_service_id" value="{{ $upgrade->id }}">
                                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded mt-2">
                                        Upgrade Now
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    <!-- Downgrade Options -->
                    <div>
                        <h4 class="font-semibold mb-2">Downgrade Options</h4>
                        @foreach($availableDowngrades as $downgrade)
                            <div class="border p-4 mb-2">
                                <h5>{{ $downgrade->name }}</h5>
                                <p>Price: {{ $downgrade->price }} {{ $downgrade->currency }}</p>
                                <form action="{{ route('client.services.downgrade', $subscription) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="new_service_id" value="{{ $downgrade->id }}">
                                    <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded mt-2">
                                        Schedule Downgrade
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Cancel Service -->
                <div class="mt-8 pt-4 border-t">
                    <form action="{{ route('client.services.cancel', $subscription) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to cancel this service?');">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded">
                            Cancel Service
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>