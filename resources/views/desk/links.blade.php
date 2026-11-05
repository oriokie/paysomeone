@foreach ([
    'account' => 'Overview',
    'desk.orders' => 'Orders',
    'desk.wallet' => 'Wallet',
    'desk.referrals' => 'Referrals',
    'desk.notifications' => 'Alerts',
    'desk.profile' => 'Profile',
] as $route => $label)
    <a href="{{ route($route) }}" class="rounded-xl px-3 py-2.5 text-sm {{ request()->routeIs($route) ? 'bg-white/10 font-semibold text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
@endforeach
<a href="{{ route('order.create') }}" class="btn btn-invert mt-4">New order</a>
