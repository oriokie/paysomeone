@extends('layouts.desk')
@section('title', 'Overview — '.$siteName)
@section('heading', 'Overview')

@section('content')
    <p class="font-display text-[32px] tracking-tight text-navy">Hello, {{ $customer->name ?: 'there' }}.</p>
    <p class="mt-2 max-w-xl text-slate-500">Here is what needs you, and what is already moving.</p>

    <dl class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ui-card p-6"><dt class="text-[13px] text-slate-500">Active orders</dt><dd class="mt-2 font-display text-[32px] tabular-nums tracking-tight">{{ $open }}</dd></div>
        <div class="ui-card p-6"><dt class="text-[13px] text-slate-500">Awaiting payment</dt><dd class="mt-2 font-display text-[32px] tabular-nums tracking-tight">{{ $unpaid }}</dd></div>
        <div class="ui-card p-6"><dt class="text-[13px] text-slate-500">Wallet</dt><dd class="mt-2 font-display text-[32px] tabular-nums tracking-tight">${{ number_format((float) $customer->wallet_balance, 2) }}</dd></div>
        <div class="ui-card p-6"><dt class="text-[13px] text-slate-500">Unread messages</dt><dd class="mt-2 font-display text-[32px] tabular-nums tracking-tight">{{ $unreadMessages }}</dd></div>
    </dl>

    <section class="mt-10">
        <h2 class="font-display text-2xl tracking-tight text-navy">Action needed</h2>
        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            @foreach (['Pay' => $unpaidOrders, 'Review' => $ready, 'Revision' => $revisions] as $label => $rows)
                <div class="ui-card p-5">
                    <h3 class="text-[13px] font-medium text-slate-500">{{ $label }}</h3>
                    <ul class="mt-3 space-y-3">
                        @forelse ($rows as $order)
                            <li><a class="block text-[15px] font-medium hover:text-navy" href="{{ route('desk.order', $order) }}">#{{ $order->order_id }} {{ $order->title }}</a></li>
                        @empty
                            <li class="text-[13px] text-slate-400">Nothing here.</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-10">
        <div class="flex items-end justify-between">
            <h2 class="font-display text-2xl tracking-tight text-navy">Recent orders</h2>
            <a class="text-[13px] font-medium text-navy" href="{{ route('desk.orders') }}">All orders</a>
        </div>
        <div class="ui-card mt-4 divide-y divide-slate-100">
            @forelse ($orders as $order)
                <a class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50" href="{{ route('desk.order', $order) }}">
                    <span>
                        <span class="block text-[15px] font-medium">#{{ $order->order_id }} {{ $order->title }}</span>
                        <span class="mt-1 block text-[13px] text-slate-500">{{ $order->serviceLabel() }}</span>
                    </span>
                    <x-pill :status="$order->status_id" :label="$order->statusLabel()" />
                </a>
            @empty
                <div class="px-5 py-14 text-center">
                    <x-glyph name="orders" class="mx-auto size-8 text-slate-300" />
                    <p class="mt-3 font-display text-xl text-navy">No orders yet</p>
                    <a class="ui-btn ui-btn-primary mt-5" href="{{ route('order.create') }}">Place an order</a>
                </div>
            @endforelse
        </div>
    </section>

    <section class="mt-10">
        <div class="flex items-end justify-between">
            <h2 class="font-display text-2xl tracking-tight text-navy">Recent messages</h2>
            <a class="text-[13px] font-medium text-navy" href="{{ route('desk.messages') }}">All messages</a>
        </div>
        <div class="ui-card mt-4 divide-y divide-slate-100">
            @forelse ($messages as $message)
                <a class="block px-5 py-4 hover:bg-slate-50" href="{{ route('desk.order', $message->order_id) }}?tab=messages">
                    <span class="text-[13px] text-slate-500">Order #{{ $message->order_id }}</span>
                    <span class="mt-1 block truncate text-[15px]">{{ \Illuminate\Support\Str::limit($message->body, 120) }}</span>
                </a>
            @empty
                <div class="px-5 py-14 text-center">
                    <x-glyph name="message" class="mx-auto size-8 text-slate-300" />
                    <p class="mt-3 font-display text-xl text-navy">No messages yet</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
