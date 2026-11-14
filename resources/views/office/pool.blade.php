@extends('layouts.office')
@section('title', 'Available orders — '.$siteName)
@section('heading', 'Available orders')

@section('content')
    <p class="text-slate-500">Orders published for writers. Your category {{ (int) $staff->writer_category === 0 ? 'is open' : 'is '.$staff->writer_category }}.</p>
    <div class="mt-6 space-y-4">
        @forelse ($orders as $order)
            <article class="ui-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[13px] text-slate-500">#{{ $order->order_id }} · {{ $order->serviceLabel() }}</p>
                        <h2 class="font-display text-xl tracking-tight">{{ $order->title }}</h2>
                        <p class="mt-2 text-[13px] text-slate-500">{{ $order->pages ? $order->pages.' pages' : $order->words.' words' }} · due {{ $order->customer_deadline?->diffForHumans() ?? 'soon' }}</p>
                    </div>
                    <x-pill :status="$order->status_id" :label="$order->statusLabel()" />
                </div>
                <p class="mt-3 line-clamp-3 text-[15px] text-slate-600">{{ $order->paper_details }}</p>
                @if (in_array($order->order_id, $mine, true))
                    <p class="mt-3 text-[13px] font-medium text-good">You already have a bid on this order.</p>
                @endif
                <form class="mt-4 flex flex-wrap items-end gap-3" method="POST" action="{{ route('office.pool.bid', $order) }}">
                    @csrf
                    <div>
                        <label class="ui-label" for="amount-{{ $order->order_id }}">Your price</label>
                        <input id="amount-{{ $order->order_id }}" name="amount" type="number" min="1" step="0.01" required class="ui-input w-32">
                    </div>
                    <div>
                        <label class="ui-label" for="hours-{{ $order->order_id }}">Hours</label>
                        <input id="hours-{{ $order->order_id }}" name="hours" type="number" min="1" required class="ui-input w-28" value="48">
                    </div>
                    <button class="ui-btn ui-btn-primary" type="submit">Place bid</button>
                </form>
            </article>
        @empty
            <div class="ui-card px-6 py-16 text-center">
                <x-glyph name="search" class="mx-auto size-8 text-slate-300" />
                <p class="mt-3 font-display text-xl text-navy">The pool is empty</p>
                <p class="mt-2 text-[13px] text-slate-500">Orders appear here when they are published to writers.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
