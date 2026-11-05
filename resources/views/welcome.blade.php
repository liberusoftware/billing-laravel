<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} · Hosting for your next chapter</title>
    <meta name="description" content="Explore hosting services, choose a subscription, and manage your invoices, projects, and support from one customer account.">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-4 focus:text-slate-900">Skip to content</a>
    <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-5" aria-label="Main navigation">
            <a href="{{ route('storefront') }}" class="text-xl font-bold tracking-tight">{{ config('app.name') }}<span class="text-teal-600">.</span></a>
            <div class="flex flex-wrap items-center gap-6 text-sm font-semibold">
                <a href="#plans" class="hover:text-teal-600">Hosting &amp; services</a>
                <a href="#how-it-works" class="hover:text-teal-600">How it works</a>
                <a href="{{ url('/client') }}" class="rounded-lg bg-slate-900 px-5 py-3 text-white hover:bg-teal-800 dark:bg-teal-700">Customer portal <span aria-hidden="true">↗</span></a>
            </div>
        </nav>
    </header>
    <main id="main">
        <section class="relative overflow-hidden bg-slate-900 text-white">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:py-28">
                <div class="flex flex-col items-start gap-7">
                    <p class="text-xs font-semibold uppercase tracking-widest text-teal-300">Your business. Room to grow.</p>
                    <h1 class="max-w-2xl text-5xl font-semibold leading-tight tracking-tight sm:text-6xl">A home for your website.<br><span class="text-teal-300">A clear path forward.</span></h1>
                    <p class="max-w-xl text-lg leading-relaxed text-slate-300">Choose the services that fit your next step. Keep hosting subscriptions, payments, project updates, and support together in one customer account.</p>
                    <a href="#plans" class="rounded-lg bg-teal-300 px-6 py-4 font-semibold text-slate-950 hover:bg-teal-200">Explore services <span aria-hidden="true">→</span></a>
                    <p class="text-sm text-slate-400">Compare plans first. Review your billing cycle before you order.</p>
                </div>
                <div class="rounded-2xl border border-slate-700 bg-slate-800 p-7 shadow-2xl sm:p-10">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-700 pb-6">
                        <span class="text-sm font-semibold text-slate-300">ONE ACCOUNT, EVERYTHING IN VIEW</span>
                        <span class="h-3 w-3 rounded-full bg-teal-300" aria-hidden="true"></span>
                    </div>
                    @foreach ([['01', 'Your services', 'View subscriptions and request plan changes.'], ['02', 'Your billing', 'Find invoices and track payments in one place.'], ['03', 'Your projects', 'Follow work, share updates, and keep support connected.']] as [$number, $title, $description])
                        <div class="flex gap-5 border-b border-slate-700 py-6 last:border-0 last:pb-0">
                            <span class="text-sm font-semibold text-teal-300" aria-hidden="true">{{ $number }}</span>
                            <div class="flex flex-col gap-2"><h2 class="text-xl font-semibold">{{ $title }}</h2><p class="leading-relaxed text-slate-400">{{ $description }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <section id="plans" class="mx-auto max-w-7xl scroll-mt-8 px-6 py-20">
            <div class="mb-12 flex max-w-2xl flex-col gap-4">
                <p class="text-xs font-bold uppercase tracking-widest text-teal-700 dark:text-teal-300">Find your fit</p>
                <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">The next step starts here.</h2>
                <p class="leading-relaxed text-slate-600 dark:text-slate-400">Browse our available services. Choose your plan and billing cycle at checkout to see the full amount before placing your order.</p>
            </div>
            @forelse ($offers as $offer)
                <div class="mb-12">
                    <h3 class="mb-3 text-2xl font-semibold">{{ $offer['template']->name }}</h3>
                    @if ($offer['template']->description)<p class="mb-6 max-w-3xl text-slate-600 dark:text-slate-400">{{ $offer['template']->description }}</p>@endif
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($offer['plans'] as $plan)
                            <article class="flex flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <h4 class="text-xl font-semibold">{{ $plan->name }}</h4>
                                <p class="text-slate-600 dark:text-slate-400">{{ $plan->description }}</p>
                                <p><span class="text-4xl font-semibold tracking-tight">{{ number_format((float) $plan->price, 2) }}</span> <span class="text-sm text-slate-500 dark:text-slate-400">{{ $plan->currency }} / base month</span></p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Your selected billing period determines the total due at checkout.</p>
                                @if ($plan->features)
                                    <ul class="flex flex-col gap-3 text-sm">
                                        @foreach ($plan->features as $feature)
                                            @if (is_string($feature))<li class="flex gap-3"><span class="text-teal-600" aria-hidden="true">✓</span><span>{{ $feature }}</span></li>@endif
                                        @endforeach
                                    </ul>
                                @endif
                                <a href="{{ $offer['checkout_url'] }}&amp;plan={{ $plan->id }}" class="mt-auto rounded-lg bg-teal-700 px-5 py-3 text-center font-semibold text-white hover:bg-teal-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal-600">Choose {{ $plan->name }} <span aria-hidden="true">→</span></a>
                            </article>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-10 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-3 text-xl font-semibold">We’re preparing our service catalogue.</h3>
                    <p class="text-slate-600 dark:text-slate-400">Existing customers can access their services, invoices, and support in the <a href="{{ url('/client') }}" class="font-semibold text-teal-700 underline dark:text-teal-300">customer portal</a>.</p>
                </div>
            @endforelse
        </section>
        <section id="how-it-works" class="border-y border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto max-w-7xl px-6 py-16">
                <h2 class="mb-10 text-3xl font-semibold tracking-tight">From choosing to getting started.</h2>
                <ol class="grid gap-10 md:grid-cols-3">
                    @foreach ([['Choose your service', 'Compare available plans and select the one that fits your needs.'], ['Review your order', 'Sign in, select your billing cycle, and review the total. For hosting, bring a domain you already own.'], ['Pay and follow progress', 'Complete your invoice payment and follow service activation from your customer account. Domain registration and transfer are separate services.']] as [$title, $description])
                        <li class="flex flex-col gap-3"><span class="text-sm font-bold text-teal-700 dark:text-teal-300">0{{ $loop->iteration }}</span><h3 class="text-lg font-semibold">{{ $title }}</h3><p class="leading-relaxed text-slate-600 dark:text-slate-400">{{ $description }}</p></li>
                    @endforeach
                </ol>
            </div>
        </section>
    </main>
    <footer class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-5 px-6 py-8 text-sm text-slate-500 dark:text-slate-400">
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
        <a href="{{ url('/client') }}" class="font-semibold hover:text-teal-700">Manage your account <span aria-hidden="true">→</span></a>
    </footer>
</body>
</html>
