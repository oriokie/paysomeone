<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1023">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Desk — '.$siteName)</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-paper-raised focus:px-4 focus:py-2">Skip to content</a>
    @if ($readOnly)
        <div class="bg-navy px-4 py-2 text-center text-xs text-white/90 lg:pl-64">Preview. Changes to live orders, files, and payments are blocked.</div>
    @endif
    <div class="lg:grid lg:grid-cols-[16rem_1fr]">
        <aside class="sticky top-0 hidden h-screen flex-col border-r border-line bg-navy text-white lg:flex">
            <a href="{{ route('home') }}" class="px-6 py-6 font-display text-lg">{{ $siteName }}</a>
            <nav class="flex flex-1 flex-col gap-1 px-3" aria-label="Desk">
                @include('desk.links')
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="p-4">
                @csrf
                <button class="btn btn-on-dark w-full" type="submit">Sign out</button>
            </form>
        </aside>
        <div class="min-w-0">
            <header class="flex items-center justify-between border-b border-line bg-paper/80 px-5 py-4 backdrop-blur lg:hidden">
                <a href="{{ route('account') }}" class="font-display text-lg text-navy">Desk</a>
                <a href="{{ route('order.create') }}" class="btn btn-primary">New brief</a>
            </header>
            <main id="content" class="px-5 py-8 pb-28 lg:px-10 lg:pb-12">
                @yield('content')
            </main>
        </div>
    </div>
    <nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-line bg-paper-raised/95 px-1 py-2 text-center text-[11px] backdrop-blur lg:hidden" aria-label="Desk">
        @foreach (['account' => 'Home', 'desk.orders' => 'Orders', 'desk.wallet' => 'Wallet', 'desk.notifications' => 'Alerts', 'desk.profile' => 'Profile'] as $route => $label)
            <a href="{{ route($route) }}" class="rounded-xl py-2 {{ request()->routeIs($route) ? 'font-semibold text-navy' : 'text-muted' }}" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @if (session('status') || $errors->any())
        <div class="toast" role="status" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-start justify-between gap-4">
                <p>{{ session('status') ?: $errors->first() }}</p>
                <button type="button" class="text-muted" @click="show = false" aria-label="Dismiss">×</button>
            </div>
        </div>
    @endif
</body>
</html>
