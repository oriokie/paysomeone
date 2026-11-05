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
<body class="min-h-screen bg-app font-sans text-[15px] text-slate-900 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2">Skip to content</a>
    <div class="lg:grid lg:grid-cols-[240px_minmax(0,1fr)]">
        <aside class="sticky top-0 hidden h-screen flex-col bg-navy text-white lg:flex">
            <a href="{{ route('account') }}" class="px-5 py-6 font-display text-lg tracking-tight">{{ $siteName }}</a>
            <nav class="flex flex-1 flex-col gap-1 px-3" aria-label="Desk">
                @include('desk.links')
            </nav>
            <div class="border-t border-white/10 p-3" x-data="{ open: false }">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left hover:bg-white/5" @click="open = !open" aria-expanded="false" :aria-expanded="open">
                    <span class="grid size-8 place-items-center rounded-full bg-white/10 text-[13px] font-semibold">{{ strtoupper(substr(auth('customer')->user()->name ?: 'Y', 0, 1)) }}</span>
                    <span class="min-w-0">
                        <span class="block truncate text-[13px] font-medium">{{ auth('customer')->user()->name ?: 'Account' }}</span>
                        <span class="block truncate text-[13px] text-white/50">{{ auth('customer')->user()->email }}</span>
                    </span>
                </button>
                <form method="POST" action="{{ route('logout') }}" x-show="open" x-cloak class="px-2 pt-2">
                    @csrf
                    <button class="text-[13px] text-white/70 hover:text-white" type="submit">Sign out</button>
                </form>
            </div>
        </aside>
        <div class="min-w-0">
            @if ($readOnly)
                <div class="bg-navy px-4 py-2 text-center text-[13px] text-white/80">Preview. Changes to live orders, files, and payments are blocked.</div>
            @endif
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200/70 bg-app/90 px-4 backdrop-blur lg:px-8">
                <h1 class="hidden font-display text-lg tracking-tight text-navy sm:block">@yield('heading', 'Overview')</h1>
                <form class="relative ml-auto hidden w-full max-w-xs md:block" method="GET" action="{{ route('desk.orders') }}" role="search">
                    <x-glyph name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <label class="sr-only" for="desk-search">Search orders</label>
                    <input id="desk-search" name="q" value="{{ request('q') }}" placeholder="Search orders" class="h-10 w-full rounded-full border border-slate-200/80 bg-white pr-3 pl-9 text-[13px] outline-none focus:ring-4 focus:ring-accent/15">
                </form>
                <div class="relative ml-auto md:ml-0" x-data="{ open: false }">
                    <button type="button" class="relative grid size-10 place-items-center rounded-full hover:bg-white" @click="open = !open" aria-label="Notifications">
                        <x-glyph name="bell" class="size-5" />
                        @if (($deskUnread ?? 0) > 0)
                            <span class="absolute top-1.5 right-1.5 size-2 rounded-full bg-accent"></span>
                        @endif
                    </button>
                    <div class="ui-card absolute right-0 z-20 mt-2 w-80 p-2" x-show="open" x-cloak @click.outside="open = false">
                        @forelse (($deskNotes ?? collect()) as $note)
                            <a class="block rounded-xl px-3 py-2 hover:bg-slate-50" href="{{ route('desk.notifications') }}">
                                <span class="block text-[13px]">{{ $note->message }}</span>
                                <span class="text-[13px] text-slate-400">{{ $note->is_seen ? 'Seen' : 'New' }}</span>
                            </a>
                        @empty
                            <p class="px-3 py-4 text-[13px] text-slate-500">You’re caught up.</p>
                        @endforelse
                        <a class="block px-3 py-2 text-[13px] font-medium text-navy" href="{{ route('desk.notifications') }}">All notifications</a>
                    </div>
                </div>
                <a href="{{ route('order.create') }}" class="ui-btn ui-btn-primary hidden h-10 lg:inline-flex">New order</a>
            </header>
            <main id="content" class="mx-auto max-w-[1200px] px-4 py-8 pb-28 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>
    <nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-slate-200/80 bg-white/95 px-1 py-2 text-center text-[11px] backdrop-blur lg:hidden" aria-label="Desk">
        @foreach ([
            'account' => ['Overview', 'home'],
            'desk.orders' => ['Orders', 'orders'],
            'desk.messages' => ['Messages', 'message'],
            'desk.wallet' => ['Wallet', 'wallet'],
            'desk.profile' => ['Settings', 'settings'],
        ] as $route => [$label, $icon])
            <a href="{{ route($route) }}" class="flex flex-col items-center gap-1 rounded-xl py-1 {{ request()->routeIs($route) ? 'font-semibold text-navy' : 'text-slate-400' }}" @if (request()->routeIs($route)) aria-current="page" @endif>
                <x-glyph :name="$icon" class="size-5" />
                {{ $label }}
            </a>
        @endforeach
    </nav>
    <a href="{{ route('order.create') }}" class="ui-btn ui-btn-primary fixed right-4 bottom-20 z-40 size-12 rounded-full p-0 shadow-lg lg:hidden" aria-label="New order">
        <x-glyph name="plus" class="size-5" />
    </a>
    @if (session('status') || $errors->any())
        <div class="toast" role="status" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-start justify-between gap-4">
                <p>{{ session('status') ?: $errors->first() }}</p>
                <button type="button" class="text-slate-400" @click="show = false" aria-label="Dismiss">×</button>
            </div>
        </div>
    @endif
</body>
</html>
