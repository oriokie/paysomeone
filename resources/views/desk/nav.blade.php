<nav class="border-b border-line bg-white" aria-label="Your desk">
    <div class="mx-auto flex max-w-6xl gap-2 overflow-x-auto px-5 py-3 text-sm">
        @foreach ([
            'account' => 'Overview',
            'desk.orders' => 'Orders',
            'desk.wallet' => 'Wallet',
            'desk.referrals' => 'Referrals',
            'desk.notifications' => 'Alerts',
            'desk.profile' => 'Profile',
        ] as $route => $label)
            <a href="{{ route($route) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs($route) ? 'bg-navy text-white' : 'text-ink/70' }}" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </div>
</nav>
