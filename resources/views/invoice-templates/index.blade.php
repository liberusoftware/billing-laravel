

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Invoice Templates') }}
            </h2>
            <x-button-link href="{{ route('invoice-templates.create') }}">
                Create Template
            </x-button-link>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    @if($templates->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-gray-500">No templates found. Create your first template to get started.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($templates as $template)
                                <div class="border rounded-lg p-4 relative">
                                    @if($template->is_default)
                                        <span class="absolute top-2 right-2 bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                            Default
                                        </span>
                                    @endif
                                    
                                    <div class="mb-4">
                                        <h3 class="text-lg font-semibold">{{ $template->name }}</h3>
                                        <p class="text-sm text-gray-600">{{ $template->company_name }}</p>
                                    </div>

                                    @if($template->logo_path)
                                        <div class="mb-4">
                                            <img src="{{ Storage::disk('public')->url($template->logo_path) }}" 
                                                alt="Logo" 
                                                class="max-h-12 object-contain">
                                        </div>
                                    @endif

                                    <div class="flex space-x-2">
                                        <x-button-link href="{{ route('invoice-templates.edit', $template) }}" class="text-sm">
                                            Edit
                                        </x-button-link>
                                        <x-button-link href="{{ route('invoice-templates.preview', $template) }}" class="text-sm">
                                            Preview
                                        </x-button-link>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>