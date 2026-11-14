@extends('layouts.desk')
@section('title', 'Orders — '.$siteName)
@section('heading', 'Orders')

@section('content')
    <div x-data="{ ready: false }" x-init="ready = true">
        <div class="flex flex-wrap gap-2" role="tablist">
            @foreach ($filters as $key => $label)
                <a class="rounded-full px-3 py-1.5 text-[13px] {{ $status === $key ? 'bg-navy text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200/80' }}" href="{{ route('desk.orders', ['status' => $key, 'q' => request('q')]) }}" @if ($status === $key) aria-current="page" @endif>{{ $label }} <span class="tabular-nums">{{ $counts[$key] ?? 0 }}</span></a>
            @endforeach
        </div>
        <form class="mt-4 md:hidden" method="GET">
            <input type="hidden" name="status" value="{{ $status }}">
            <label class="sr-only" for="q">Search titles</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Search titles" class="ui-input">
        </form>

        <div class="mt-4 space-y-3" x-show="!ready">
            @foreach (range(1, 4) as $row)
                <div class="h-16 animate-pulse rounded-2xl bg-white"></div>
            @endforeach
        </div>

        <div x-show="ready" x-cloak>
            <div class="ui-card mt-2 hidden overflow-hidden md:block">
                <table class="w-full text-left text-[15px]">
                    <thead class="text-[13px] text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">ID</th>
                            <th class="px-5 py-3 font-medium">Title</th>
                            <th class="px-5 py-3 font-medium">Service</th>
                            <th class="px-5 py-3 font-medium">Deadline</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="cursor-pointer border-t border-slate-100 hover:bg-slate-50" onclick="window.location='{{ route('desk.order', $order) }}'">
                                <td class="px-5 py-4 tabular-nums"><a class="font-medium" href="{{ route('desk.order', $order) }}">#{{ $order->order_id }}</a></td>
                                <td class="px-5 py-4">{{ $order->title }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $order->serviceLabel() }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $order->customer_deadline ? $order->customer_deadline->diffForHumans() : '—' }}</td>
                                <td class="px-5 py-4"><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></td>
                                <td class="px-5 py-4 tabular-nums">${{ number_format((float) $order->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-16 text-center text-slate-500">Nothing in {{ strtolower($filters[$status]) }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 space-y-3 md:hidden">
                @foreach ($orders as $order)
                    <a class="ui-card block p-4" href="{{ route('desk.order', $order) }}">
                        <span class="text-[13px] text-slate-500">#{{ $order->order_id }} · {{ $order->serviceLabel() }}</span>
                        <span class="mt-1 block font-medium">{{ $order->title }}</span>
                        <span class="mt-3 flex items-center justify-between">
                            <x-pill :status="$order->status_id" :label="$order->statusLabel()" />
                            <span class="tabular-nums">${{ number_format((float) $order->total, 2) }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $orders->links() }}</div>
        </div>
    </div>
@endsection
