@extends('layouts.desk')
@section('title', 'Dashboard — '.$siteName)

@section('content')
    <p class="eyebrow">Dashboard</p>
    <h1 class="mt-2 font-display text-4xl text-navy">Hello, {{ $customer->name ?: 'there' }}.</h1>
    <p class="mt-3 max-w-2xl text-muted">Recent orders, unpaid orders, and the wallet balance on your account.</p>
    <dl class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <dt class="text-xs font-semibold tracking-wide text-muted uppercase">Wallet</dt>
            <dd class="mt-2 font-display text-4xl text-navy">${{ number_format((float) $customer->wallet_balance, 2) }}</dd>
            <a class="mt-3 inline-block text-sm font-semibold text-accent" href="{{ route('desk.wallet') }}">Wallet history</a>
        </div>
        <div class="card p-5">
            <dt class="text-xs font-semibold tracking-wide text-muted uppercase">Unpaid</dt>
            <dd class="mt-2 font-display text-4xl text-navy">{{ $unpaid }}</dd>
            <a class="mt-3 inline-block text-sm font-semibold text-accent" href="{{ route('desk.orders', ['status' => 'unpaid']) }}">Waiting for payment</a>
        </div>
        <div class="card p-5">
            <dt class="text-xs font-semibold tracking-wide text-muted uppercase">Open</dt>
            <dd class="mt-2 font-display text-4xl text-navy">{{ $open }}</dd>
            <a class="mt-3 inline-block text-sm font-semibold text-accent" href="{{ route('desk.notifications') }}">{{ $alerts }} unread {{ $alerts === 1 ? 'alert' : 'alerts' }}</a>
        </div>
    </dl>
    <div class="mt-10 flex items-end justify-between gap-4">
        <h2 class="font-display text-2xl text-navy">Recent orders</h2>
        <a class="text-sm font-semibold text-accent" href="{{ route('desk.orders') }}">All recent</a>
    </div>
    <div class="card mt-4 hidden overflow-hidden md:block">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Deadline</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><a class="font-medium text-navy" href="{{ route('desk.order', $order) }}">#{{ $order->order_id }}</a></td>
                        <td><a class="font-medium" href="{{ route('desk.order', $order) }}">{{ $order->title }}</a></td>
                        <td><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></td>
                        <td>{{ $order->customer_deadline ? \Illuminate\Support\Carbon::parse($order->customer_deadline)->timezone(config('app.timezone'))->format('j M Y') : '—' }}</td>
                        <td>${{ number_format((float) $order->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center">
                            <p class="font-display text-xl text-navy">No recent orders</p>
                            <a class="btn btn-primary mt-5" href="{{ route('order.create') }}">Place an order</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 space-y-3 md:hidden">
        @forelse ($orders as $order)
            <a class="card block p-4" href="{{ route('desk.order', $order) }}">
                <p class="text-xs text-muted">#{{ $order->order_id }}</p>
                <p class="mt-1 font-medium">{{ $order->title }}</p>
                <p class="mt-2"><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></p>
                <p class="mt-2 text-sm text-muted">${{ number_format((float) $order->total, 2) }}</p>
            </a>
        @empty
            <div class="card px-5 py-12 text-center">
                <p class="font-display text-xl text-navy">No recent orders</p>
                <a class="btn btn-primary mt-5" href="{{ route('order.create') }}">Place an order</a>
            </div>
        @endforelse
    </div>
@endsection
