@extends('layouts.desk')
@section('title', 'My orders — '.$siteName)

@section('content')
    <h1 class="font-display text-4xl text-navy">My orders</h1>
    <p class="mt-2 text-sm text-muted">Recent, unpaid, finished, and canceled. The same groups the current dashboard uses.</p>
    <div class="mt-6 flex flex-wrap items-center gap-2">
        @foreach ($filters as $key => $label)
            <a class="rounded-full border px-3 py-1.5 text-sm {{ $status === $key ? 'border-navy bg-navy text-white' : 'border-line bg-paper-raised text-ink' }}" href="{{ route('desk.orders', ['status' => $key, 'q' => request('q')]) }}" @if ($status === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
        <form method="GET">
            <input type="hidden" name="status" value="{{ $status }}">
            <label class="sr-only" for="q">Search titles</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Search titles" class="field mt-0 max-w-xs rounded-full">
        </form>
    </div>
    <div class="card mt-6 hidden overflow-hidden md:block">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
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
                        <td>{{ $order->serviceLabel() }}</td>
                        <td><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></td>
                        <td>{{ $order->customer_deadline ? \Illuminate\Support\Carbon::parse($order->customer_deadline)->timezone(config('app.timezone'))->format('j M Y, H:i') : '—' }}</td>
                        <td>${{ number_format((float) $order->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-muted">Nothing in {{ strtolower($filters[$status]) }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6 space-y-3 md:hidden">
        @foreach ($orders as $order)
            <a class="card block p-4" href="{{ route('desk.order', $order) }}">
                <p class="text-xs text-muted">#{{ $order->order_id }} · {{ $order->serviceLabel() }}</p>
                <p class="mt-1 font-medium">{{ $order->title }}</p>
                <p class="mt-2"><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></p>
                <p class="mt-2 text-sm">${{ number_format((float) $order->total, 2) }}</p>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
