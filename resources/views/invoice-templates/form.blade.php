

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($template) ? 'Edit Template' : 'Create Template' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ isset($template) ? route('invoice-templates.update', $template) : route('invoice-templates.store') }}" enctype="multipart/form-data">
                @csrf
                @if(isset($template))
                    @method('PUT')
                @endif

                <div class="space-y-6">
                    <div>
                        <x-label for="name" value="Template Name" />
                        <x-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $template->name ?? '')" required />
                    </div>

                    <div>
                        <x-label for="company_name" value="Company Name" />
                        <x-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', $template->company_name ?? '')" required />
                    </div>

                    <div>
                        <x-label for="company_address" value="Company Address" />
                        <x-textarea id="company_address" name="company_address" class="mt-1 block w-full">{{ old('company_address', $template->company_address ?? '') }}</x-textarea>
                    </div>

                    <div>
                        <x-label for="company_phone" value="Company Phone" />
                        <x-input id="company_phone" name="company_phone" type="text" class="mt-1 block w-full" :value="old('company_phone', $template->company_phone ?? '')" />
                    </div>

                    <div>
                        <x-label for="company_email" value="Company Email" />
                        <x-input id="company_email" name="company_email" type="email" class="mt-1 block w-full" :value="old('company_email', $template->company_email ?? '')" />
                    </div>

                    <div>
                        <x-label for="logo" value="Company Logo" />
                        <input type="file" name="logo" accept="image/*" class="mt-1 block w-full" />
                    </div>

                    <div>
                        <x-label for="header_text" value="Header Text" />
                        <x-textarea id="header_text" name="header_text" class="mt-1 block w-full">{{ old('header_text', $template->header_text ?? '') }}</x-textarea>
                    </div>

                    <div>
                        <x-label for="footer_text" value="Footer Text" />
                        <x-textarea id="footer_text" name="footer_text" class="mt-1 block w-full">{{ old('footer_text', $template->footer_text ?? '') }}</x-textarea>
                    </div>

                    <div>
                        <x-label for="color_scheme" value="Color Scheme" />
                        <input type="color" name="color_scheme" class="mt-1 block" :value="old('color_scheme', $template->color_scheme ?? '#000000')" />
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_default" class="form-checkbox" :checked="old('is_default', $template->is_default ?? false)">
                            <span class="ml-2">Set as default template</span>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <x-button>
                            {{ isset($template) ? 'Update Template' : 'Create Template' }}
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>