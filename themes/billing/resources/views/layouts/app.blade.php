<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), config('localization.rtl_locales', []), true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'BillingOS'))</title>
    <link rel="canonical" href="{{ url()->current() }}">
    @themeVite
    @livewireStyles
    @stack('head')
</head>
<body>
    <a class="skip-link" href="#main-content">{{ __('Skip to content') }}</a>
    <div class="billing-shell">
        <aside class="billing-sidebar" data-billing-sidebar data-open="false" aria-label="{{ __('Billing navigation') }}">
            <a class="billing-brand" href="{{ url('/app') }}"><span class="billing-brand-mark" aria-hidden="true">₿</span><span>{{ config('app.name', 'BillingOS') }}</span></a>
            <nav class="billing-nav">
                <p class="billing-nav-label">{{ __('Workspace') }}</p>
                <a href="{{ url('/app') }}" @if(request()->is('app')) aria-current="page" @endif><span aria-hidden="true">⌂</span>{{ __('Overview') }}</a>
                <a href="{{ url('/app/invoices') }}"><span aria-hidden="true">▤</span>{{ __('Invoices') }}</a>
                <a href="{{ url('/app/clients') }}"><span aria-hidden="true">♙</span>{{ __('Customers') }}</a>
                <a href="{{ url('/app/services') }}"><span aria-hidden="true">◈</span>{{ __('Services') }}</a>
                <p class="billing-nav-label">{{ __('Manage') }}</p>
                <a href="{{ url('/app/reports') }}"><span aria-hidden="true">◒</span>{{ __('Reports') }}</a>
                <a href="{{ url('/tickets') }}"><span aria-hidden="true">✉</span>{{ __('Support') }}</a>
            </nav>
            <div class="billing-sidebar-footer">{{ __('A clearer view of your business.') }}</div>
        </aside>
        <div class="billing-content">
            <header class="billing-topbar">
                <div class="billing-breadcrumb"><button class="billing-mobile-menu" type="button" data-billing-menu aria-expanded="false" aria-label="{{ __('Open navigation') }}">☰</button><span>{{ __('Billing workspace') }}</span></div>
                <div class="billing-user"><span>{{ auth()->user()?->name }}</span><span class="billing-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}</span></div>
            </header>
            <main id="main-content" class="billing-main" tabindex="-1">@yield('content')</main>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
