<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $siteName)</title>
    <meta name="description" content="@yield('description', 'Professional writing for businesses: articles, editing, proofreading, rewriting, business documents, and resumes.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('title', $siteName)">
    <meta property="og:description" content="@yield('description', 'Professional writing for businesses and professionals.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-sand font-sans text-ink antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:px-4 focus:py-2">Skip to content</a>

    @if ($readOnly)
        <div class="bg-navy px-4 py-2 text-center text-sm text-white">
            Preview of the new site. The current website is still taking live orders. This copy does not change that database.
        </div>
    @endif

    <header class="border-b border-line bg-mist/90 backdrop-blur" x-data="{ open: false }">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-5 py-4">
            <a href="{{ route('home') }}" class="font-display text-lg font-semibold tracking-tight text-navy">{{ $siteName }}</a>
            <nav class="hidden items-center gap-7 text-sm text-ink/80 lg:flex" aria-label="Primary">
                @foreach (['services' => 'Services', 'how' => 'How it works', 'pricing' => 'Pricing', 'reviews' => 'Reviews', 'blog.index' => 'Journal', 'about' => 'About'] as $route => $label)
                    <a href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" class="text-navy" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="hidden items-center gap-3 lg:flex">
                @auth('customer')
                    <a href="{{ route('account') }}" class="text-sm">Account</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-ink/70" type="submit">Sign out</button></form>
                @else
                    <a href="{{ route('login') }}" class="text-sm">Sign in</a>
                @endauth
                <a href="{{ route('order.create') }}" class="rounded-full bg-navy px-4 py-2 text-sm font-medium text-white">Start a brief</a>
            </div>
            <button type="button" class="rounded-md border border-line px-3 py-2 text-sm lg:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-controls="mobile-nav">Menu</button>
        </div>
        <nav id="mobile-nav" class="space-y-3 border-t border-line px-5 py-4 text-sm lg:hidden" x-show="open" x-cloak>
            @foreach (['services' => 'Services', 'how' => 'How it works', 'pricing' => 'Pricing', 'reviews' => 'Reviews', 'blog.index' => 'Journal', 'faq' => 'FAQ', 'about' => 'About', 'contact' => 'Contact', 'order.create' => 'Start a brief', 'login' => 'Sign in'] as $route => $label)
                <a class="block" href="{{ route($route) }}">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main id="content">
        @if (session('status'))
            <div class="mx-auto mt-6 max-w-6xl px-5">
                <p class="rounded-2xl border border-line bg-white px-5 py-4 text-sm" role="status">{{ session('status') }}</p>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="mt-20 border-t border-line bg-navy text-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-5 py-14 md:grid-cols-4">
            <div class="md:col-span-2">
                <p class="font-display text-xl">{{ $siteName }}</p>
                <p class="mt-3 max-w-sm text-sm leading-6 text-white/70">A writing studio for companies and professionals. Articles, editing, rewriting, business documents, and resumes.</p>
            </div>
            <div class="text-sm leading-8 text-white/80">
                <a class="block" href="{{ route('services') }}">Services</a>
                <a class="block" href="{{ route('pricing') }}">Pricing</a>
                <a class="block" href="{{ route('faq') }}">FAQ</a>
                <a class="block" href="{{ route('contact') }}">Contact</a>
            </div>
            <div class="text-sm leading-8 text-white/80">
                <a class="block" href="{{ route('legal', 'terms') }}">Terms</a>
                <a class="block" href="{{ route('legal', 'privacy') }}">Privacy</a>
                <a class="block" href="{{ route('legal', 'refunds') }}">Refunds</a>
                <a class="block" href="{{ url('/office') }}">Staff office</a>
            </div>
        </div>
        <p class="border-t border-white/10 px-5 py-5 text-center text-xs text-white/50">Work is prepared for the client. This studio does not write material for a student to submit as their own.</p>
    </footer>
    <style>[x-cloak]{display:none !important}</style>
</body>
</html>
