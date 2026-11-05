@foreach ([
    'account' => ['Overview', 'home', null],
    'desk.orders' => ['Orders', 'orders', null],
    'desk.messages' => ['Messages', 'message', $deskUnreadMessages ?? 0],
    'desk.wallet' => ['Wallet', 'wallet', null],
    'desk.referrals' => ['Referrals', 'users', null],
    'desk.profile' => ['Settings', 'settings', null],
] as $route => [$label, $icon, $badge])
    <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] {{ request()->routeIs($route) ? 'bg-white/10 font-medium text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" @if (request()->routeIs($route)) aria-current="page" @endif>
        <x-glyph :name="$icon" class="size-5" />
        <span>{{ $label }}</span>
        @if ($badge)
            <span class="ml-auto rounded-full bg-white/15 px-2 text-[13px] tabular-nums">{{ $badge }}</span>
        @endif
    </a>
@endforeach
