@extends('layouts.site')
@section('title', 'Orders — '.$siteName)

@section('content')
    @include('desk.nav')
    <section class="mx-auto max-w-6xl px-5 py-12">
        <h1 class="font-display text-4xl text-navy">Orders</h1>
        <form class="mt-6 flex flex-wrap gap-2" method="GET">
            @foreach ($filters as $key => $label)
                <button class="rounded-full border px-3 py-1.5 text-sm {{ $status === $key ? 'border-navy bg-navy text-white' : 'border-line bg-white' }}" name="status" value="{{ $key }}" type="submit">{{ $label }}</button>
            @endforeach
            <label class="sr-only" for="q">Search titles</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Search titles" class="rounded-full border border-line bg-white px-4 py-1.5 text-sm">
        </form>
        <div class="mt-6 divide-y divide-line overflow-hidden rounded-3xl border border-line bg-white">
            @forelse ($orders as $order)
                <a class="grid gap-1 px-5 py-4 sm:grid-cols-[1fr_auto_auto] sm:items-center" href="{{ route('desk.order', $order) }}">
                    <span class="font-medium">#{{ $order->order_id }} {{ $order->title }}</span>
                    <span class="text-sm text-ink/60">{{ $order->statusLabel() }}</span>
                    <span class="text-sm">${{ number_format((float) $order->total, 2) }}</span>
                </a>
            @empty
                <p class="px-5 py-8 text-ink/70">Nothing matches that filter.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    </section>
@endsection
