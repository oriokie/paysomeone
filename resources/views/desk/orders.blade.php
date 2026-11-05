@extends('layouts.desk')
@section('title', 'Orders — '.$siteName)

@section('content')
    <h1 class="font-display text-4xl text-navy">Orders</h1>
    <form class="mt-6 flex flex-wrap gap-2" method="GET">
        @foreach ($filters as $key => $label)
            <button class="rounded-full border px-3 py-1.5 text-sm {{ $status === $key ? 'border-navy bg-navy text-white' : 'border-line bg-paper-raised text-ink' }}" name="status" value="{{ $key }}" type="submit">{{ $label }}</button>
        @endforeach
        <label class="sr-only" for="q">Search titles</label>
        <input id="q" name="q" value="{{ request('q') }}" placeholder="Search titles" class="field mt-0 max-w-xs rounded-full">
    </form>
    <div class="card mt-6 hidden overflow-hidden md:block">
        <table class="data-table">
            <thead><tr><th>Order</th><th>Status</th><th>Total</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><a class="font-medium text-navy" href="{{ route('desk.order', $order) }}">#{{ $order->order_id }} {{ $order->title }}</a></td>
                        <td><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></td>
                        <td>${{ number_format((float) $order->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-10 text-muted">Nothing matches that filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6 space-y-3 md:hidden">
        @foreach ($orders as $order)
            <a class="card block p-4" href="{{ route('desk.order', $order) }}">
                <p class="font-medium">#{{ $order->order_id }} {{ $order->title }}</p>
                <p class="mt-2"><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></p>
                <p class="mt-2 text-sm">${{ number_format((float) $order->total, 2) }}</p>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
