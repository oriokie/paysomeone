@extends('layouts.site')
@section('title', 'Your desk — '.$siteName)

@section('content')
    @include('desk.nav')
    <section class="mx-auto max-w-6xl px-5 py-12">
        <h1 class="font-display text-4xl text-navy">Hello, {{ $customer->name ?: 'there' }}.</h1>
        <p class="mt-3 max-w-2xl leading-7 text-ink/70">Orders, files, and messages from the studio’s existing records. While this site is read-only, you can look but not change them.</p>
        <dl class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-3xl bg-white p-5"><dt class="text-xs tracking-wide text-ink/50 uppercase">Wallet</dt><dd class="mt-2 font-display text-3xl">${{ number_format((float) $customer->wallet_balance, 2) }}</dd></div>
            <div class="rounded-3xl bg-white p-5"><dt class="text-xs tracking-wide text-ink/50 uppercase">Open work</dt><dd class="mt-2 font-display text-3xl">{{ $open }}</dd></div>
            <div class="rounded-3xl bg-white p-5"><dt class="text-xs tracking-wide text-ink/50 uppercase">Unread alerts</dt><dd class="mt-2 font-display text-3xl">{{ $alerts }}</dd></div>
        </dl>
        <div class="mt-10 flex items-end justify-between">
            <h2 class="font-display text-2xl text-navy">Recent orders</h2>
            <a class="text-sm underline decoration-brass underline-offset-4" href="{{ route('desk.orders') }}">All orders</a>
        </div>
        <div class="mt-4 divide-y divide-line overflow-hidden rounded-3xl border border-line bg-white">
            @forelse ($orders as $order)
                <a class="flex items-center justify-between gap-4 px-5 py-4" href="{{ route('desk.order', $order) }}">
                    <span>
                        <span class="block font-medium">#{{ $order->order_id }} {{ $order->title }}</span>
                        <span class="text-sm text-ink/60">{{ $order->statusLabel() }}</span>
                    </span>
                    <span class="text-sm">${{ number_format((float) $order->total, 2) }}</span>
                </a>
            @empty
                <p class="px-5 py-8 text-ink/70">No orders on this account yet.</p>
            @endforelse
        </div>
    </section>
@endsection
