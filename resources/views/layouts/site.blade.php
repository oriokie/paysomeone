<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1023">
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
<body class="min-h-screen font-sans text-ink antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-paper-raised focus:px-4 focus:py-2">Skip to content</a>

    <div class="sticky top-0 z-40">
        @if ($readOnly)
            <div class="bg-navy px-4 py-2 text-center text-xs text-white/90">
                Preview. The current website is still taking live orders. This copy does not change that database.
            </div>
        @endif
        <header class="border-b border-line/80 bg-paper/80 backdrop-blur-xl" x-data="{ open: false }">
            <div class="wrap flex h-16 items-center gap-6">
                <a href="{{ route('home') }}" class="font-display text-lg font-semibold text-navy">{{ $siteName }}</a>
                <nav class="hidden flex-1 items-center justify-center gap-6 text-sm text-muted lg:flex" aria-label="Primary">
                    @foreach (['services' => 'Services', 'how' => 'How it works', 'pricing' => 'Pricing', 'reviews' => 'Reviews', 'blog.index' => 'Journal'] as $route => $label)
                        <a href="{{ route($route) }}" class="transition hover:text-navy {{ request()->routeIs($route) ? 'font-semibold text-navy' : '' }}" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
                <div class="ml-auto hidden items-center gap-2 lg:flex">
                    @auth('customer')
                        <a href="{{ route('account') }}" class="btn btn-ghost">Desk</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ghost">Sign in</a>
                    @endauth
                    <a href="{{ route('order.create') }}" class="btn btn-primary">Start a brief</a>
                </div>
                <button type="button" class="btn btn-secondary ml-auto lg:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-controls="mobile-nav">
                    <span x-text="open ? 'Close' : 'Menu'">Menu</span>
                </button>
            </div>
            <div id="mobile-nav" x-show="open" x-cloak x-transition class="border-t border-line bg-paper-raised lg:hidden">
                <nav class="wrap grid gap-1 py-4 text-base" aria-label="Mobile">
                    @foreach (['services' => 'Services', 'how' => 'How it works', 'pricing' => 'Pricing', 'reviews' => 'Reviews', 'blog.index' => 'Journal', 'about' => 'About', 'faq' => 'FAQ', 'contact' => 'Contact'] as $route => $label)
                        <a class="rounded-xl px-3 py-3 hover:bg-paper" href="{{ route($route) }}">{{ $label }}</a>
                    @endforeach
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @auth('customer')
                            <a class="btn btn-secondary" href="{{ route('account') }}">Desk</a>
                        @else
                            <a class="btn btn-secondary" href="{{ route('login') }}">Sign in</a>
                        @endauth
                        <a class="btn btn-primary" href="{{ route('order.create') }}">Start a brief</a>
                    </div>
                </nav>
            </div>
        </header>
    </div>

    <main id="content">
        @yield('content')
    </main>

    <footer class="mt-24 bg-navy text-white">
        <div class="wrap grid gap-12 py-16 md:grid-cols-12">
            <div class="md:col-span-5">
                <p class="font-display text-2xl">{{ $siteName }}</p>
                <p class="mt-4 max-w-sm text-sm leading-7 text-white/70">A writing studio for companies and professionals. Articles, editing, rewriting, business documents, and resumes.</p>
            </div>
            <div class="text-sm leading-8 text-white/80 md:col-span-3">
                <p class="text-xs font-semibold tracking-[0.16em] text-accent-bright uppercase">Work</p>
                <a class="block hover:text-white" href="{{ route('services') }}">Services</a>
                <a class="block hover:text-white" href="{{ route('pricing') }}">Pricing</a>
                <a class="block hover:text-white" href="{{ route('order.create') }}">Start a brief</a>
                <a class="block hover:text-white" href="{{ route('reviews') }}">Reviews</a>
            </div>
            <div class="text-sm leading-8 text-white/80 md:col-span-2">
                <p class="text-xs font-semibold tracking-[0.16em] text-accent-bright uppercase">Studio</p>
                <a class="block hover:text-white" href="{{ route('about') }}">About</a>
                <a class="block hover:text-white" href="{{ route('faq') }}">FAQ</a>
                <a class="block hover:text-white" href="{{ route('contact') }}">Contact</a>
                <a class="block hover:text-white" href="{{ route('blog.index') }}">Journal</a>
            </div>
            <div class="text-sm leading-8 text-white/80 md:col-span-2">
                <p class="text-xs font-semibold tracking-[0.16em] text-accent-bright uppercase">Legal</p>
                <a class="block hover:text-white" href="{{ route('legal', 'terms') }}">Terms</a>
                <a class="block hover:text-white" href="{{ route('legal', 'privacy') }}">Privacy</a>
                <a class="block hover:text-white" href="{{ route('legal', 'refunds') }}">Refunds</a>
            </div>
        </div>
        <p class="border-t border-white/10 px-5 py-5 text-center text-xs text-white/50">Work is prepared for the client. This studio does not write material for a student to submit as their own.</p>
    </footer>

    @if (session('status') || $errors->any())
        <div class="toast" role="status" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-start justify-between gap-4">
                <p>{{ session('status') ?: $errors->first() }}</p>
                <button type="button" class="text-muted" @click="show = false" aria-label="Dismiss">×</button>
            </div>
        </div>
    @endif
    <style>[x-cloak]{display:none !important}</style>
</body>
</html>
