@extends('layouts.desk')
@section('title', 'Your desk — '.$siteName)

@section('content')
    <p class="eyebrow">Desk</p>
    <h1 class="mt-2 font-display text-4xl text-navy">Hello, {{ $customer->name ?: 'there' }}.</h1>
    <p class="mt-3 max-w-2xl text-muted">Orders, files, and messages from the studio’s existing records. While this site is read-only, you can look but not change them.</p>
    <dl class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="card p-5"><dt class="text-xs font-semibold tracking-wide text-muted uppercase">Wallet</dt><dd class="mt-2 font-display text-4xl text-navy">${{ number_format((float) $customer->wallet_balance, 2) }}</dd></div>
        <div class="card p-5"><dt class="text-xs font-semibold tracking-wide text-muted uppercase">Open work</dt><dd class="mt-2 font-display text-4xl text-navy">{{ $open }}</dd></div>
        <div class="card p-5"><dt class="text-xs font-semibold tracking-wide text-muted uppercase">Unread alerts</dt><dd class="mt-2 font-display text-4xl text-navy">{{ $alerts }}</dd></div>
    </dl>
    <div class="mt-10 flex items-end justify-between">
        <h2 class="font-display text-2xl text-navy">Recent orders</h2>
        <a class="text-sm font-semibold text-accent" href="{{ route('desk.orders') }}">All orders</a>
    </div>
    <div class="card mt-4 divide-y divide-line overflow-hidden">
        @forelse ($orders as $order)
            <a class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-paper" href="{{ route('desk.order', $order) }}">
                <span>
                    <span class="block font-medium">#{{ $order->order_id }} {{ $order->title }}</span>
                    <span class="mt-1 inline-flex"><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></span>
                </span>
                <span class="text-sm font-medium">${{ number_format((float) $order->total, 2) }}</span>
            </a>
        @empty
            <div class="px-5 py-12 text-center">
                <p class="font-display text-xl text-navy">No orders yet</p>
                <p class="mt-2 text-sm text-muted">When you place a brief, it will show up here.</p>
                <a class="btn btn-primary mt-5" href="{{ route('order.create') }}">Start a brief</a>
            </div>
        @endforelse
    </div>
@endsection
