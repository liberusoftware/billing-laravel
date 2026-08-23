<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Order Web Hosting Package') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('orders.store') }}">
                        @csrf

                        <div class="mb-4">
                            <x-label for="package" value="{{ __('Select Package') }}" />
                            <select id="package" class="block mt-1 w-full" name="package_id" required>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }} - ${{ number_format($package->price, 2) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <x-label for="name" value="{{ __('Full Name') }}" />
                            <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                        </div>

                        <div class="mb-4">
                            <x-label for="email" value="{{ __('Email') }}" />
                            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                        </div>

                        <div class="mb-4">
                            <x-label for="domain" value="{{ __('Domain Name') }}" />
                            <x-input id="domain" class="block mt-1 w-full" type="text" name="domain" :value="old('domain')" required />
                        </div>

                        <div class="mb-4">
                            <x-label for="currency" value="{{ __('Currency') }}" />
                            <select id="currency" class="block mt-1 w-full" name="currency" required>
                                <option value="USD">USD</option>
                                <option value="GBP">GBP</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-button class="ml-4">
                                {{ __('Place Order') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>