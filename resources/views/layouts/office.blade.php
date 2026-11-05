<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Office — '.$siteName)</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-app font-sans text-[15px] text-slate-900 antialiased">
    @php
        $staff = auth('staff')->user();
        $role = $staff ? \App\Services\Office\Permissions::roleName((int) $staff->employee_group) : '';
        $can = fn (string $key) => $staff && \App\Services\Office\Access::allows($staff, $key);
        $nav = array_values(array_filter([
            ['office.home', 'Overview', 'home', true],
            ['office.orders', 'Orders', 'orders', $can('orders.view_all') || $can('orders.view_review') || $can('orders.view_own')],
            ['office.pool', 'Pool', 'search', $can('bids.place')],
            ['office.earnings', 'Earnings', 'wallet', $can('payouts.request') || $can('payouts.approve')],
            ['office.customers', 'Customers', 'users', $can('customers.view')],
            ['office.staff', 'Staff', 'users', $can('people.manage')],
            ['office.news', 'News', 'bell', $can('news.view') || $can('news.manage')],
            ['office.disputes', 'Disputes', 'shield', $can('disputes.manage')],
            ['office.reports', 'Reports', 'file', $can('reports.view')],
            ['office.roles', 'Roles', 'settings', $can('roles.manage')],
            ['office.gateways', 'Gateways', 'card', $can('gateways.view')],
            ['office.fines', 'Fines', 'file', $can('fines.manage')],
            ['office.applications', 'Applications', 'users', $can('people.manage')],
            ['office.settings', 'Settings', 'settings', $can('settings.manage')],
            ['office.activity', 'Activity', 'clock', $can('audit.view')],
        ], fn ($item) => $item[3]));
    @endphp
    <div class="app-shell">
        <aside class="app-side">
            <a href="{{ route('office.home') }}" class="shrink-0 px-5 py-6 font-display text-lg tracking-tight">Office</a>
            <nav class="app-side-nav flex flex-col gap-1 px-3" aria-label="Office">
                @foreach ($nav as [$route, $label, $icon])
                    <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] {{ request()->routeIs($route) ? 'bg-white/10 font-medium' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                        <x-glyph :name="$icon" class="size-5" /> {{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="border-t border-white/10 p-4 text-[13px]">
                <p class="font-medium">{{ $staff?->getFilamentName() }}</p>
                <p class="text-white/50">{{ $role }}</p>
                <form method="POST" action="{{ route('office.logout') }}" class="mt-3">
                    @csrf
                    <button class="text-white/70 hover:text-white" type="submit">Sign out</button>
                </form>
            </div>
        </aside>
        <div class="app-main">
            @if (session('office.view_as'))
                <div class="flex items-center justify-between bg-amber-100 px-4 py-2 text-[13px] text-amber-950">
                    <span>Viewing as another staff member. This session cannot change anything.</span>
                    <form method="POST" action="{{ route('office.view-as.stop') }}">@csrf<button class="font-semibold" type="submit">Stop</button></form>
                </div>
            @endif
            @if ($readOnly)
                <div class="bg-navy px-4 py-2 text-center text-[13px] text-white/80">Read-only. Live order, staff, and payout rows are not changed.</div>
            @endif
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200/70 bg-app/90 px-4 backdrop-blur lg:px-8">
                <h1 class="font-display text-lg tracking-tight text-navy">@yield('heading', 'Office')</h1>
                <span class="text-[13px] text-slate-500 lg:hidden">{{ $role }}</span>
            </header>
            <main id="content" class="mx-auto max-w-[1200px] px-4 py-8 pb-28 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>
    <nav class="fixed inset-x-0 bottom-0 z-40 flex gap-1 overflow-x-auto border-t border-slate-200/80 bg-white px-2 py-2 text-[11px] lg:hidden" aria-label="Office">
        @foreach ($nav as [$route, $label, $icon])
            <a href="{{ route($route) }}" class="flex min-w-16 shrink-0 flex-col items-center gap-1 rounded-xl px-2 py-1 {{ request()->routeIs($route) ? 'font-semibold text-navy' : 'text-slate-400' }}">
                <x-glyph :name="$icon" class="size-5" /> {{ $label }}
            </a>
        @endforeach
    </nav>
    @if (session('status') || $errors->any())
        <div class="toast" role="status">{{ session('status') ?: $errors->first() }}</div>
    @endif
</body>
</html>
