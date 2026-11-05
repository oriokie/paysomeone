@extends('layouts.office')
@section('title', 'Orders — '.$siteName)
@section('heading', 'Orders')

@section('content')
    <div class="flex flex-wrap items-center gap-2">
        <a class="rounded-full px-3 py-1.5 text-[13px] {{ $layout === 'table' ? 'bg-navy text-white' : 'bg-white ring-1 ring-slate-200' }}" href="{{ route('office.orders', array_merge($filters, ['layout' => 'table'])) }}">Table</a>
        <a class="rounded-full px-3 py-1.5 text-[13px] {{ $layout === 'kanban' ? 'bg-navy text-white' : 'bg-white ring-1 ring-slate-200' }}" href="{{ route('office.orders', array_merge($filters, ['layout' => 'kanban'])) }}">Kanban</a>
        @foreach ($views as $view)
            <a class="rounded-full bg-white px-3 py-1.5 text-[13px] ring-1 ring-slate-200" href="{{ route('office.orders', ['view' => $view->id, 'layout' => $layout]) }}">{{ $view->name }}</a>
        @endforeach
    </div>
    <form class="mt-4 flex flex-wrap gap-2" method="GET">
        <input type="hidden" name="layout" value="{{ $layout }}">
        <label class="sr-only" for="q">Search</label>
        <input id="q" name="q" value="{{ $filters['q'] }}" placeholder="Title or ID" class="ui-input max-w-xs">
        <select name="status" class="ui-input max-w-[12rem]">
            <option value="">Any status</option>
            @foreach (\App\Models\Order::STATUSES as $id => $label)
                <option value="{{ $id }}" @selected((string) $filters['status'] === (string) $id)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="ui-btn ui-btn-primary h-12" type="submit">Filter</button>
    </form>
    <form class="mt-2 flex flex-wrap items-center gap-2" method="POST" action="{{ route('office.orders.views') }}">
        @csrf
        <input type="hidden" name="q" value="{{ $filters['q'] }}">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <input type="hidden" name="lane" value="{{ $filters['lane'] }}">
        <input name="name" placeholder="Save this view" class="ui-input max-w-[14rem]" required>
        <button class="ui-btn ui-btn-quiet h-12" type="submit">Save view</button>
    </form>

    @if ($layout === 'kanban')
        <div class="mt-6 flex gap-4 overflow-x-auto pb-4">
            @foreach ($kanban as $lane => $cards)
                <section class="w-72 shrink-0">
                    <h2 class="text-[13px] font-medium text-slate-500">{{ $lane }} · {{ $cards->count() }}</h2>
                    <div class="mt-2 space-y-2">
                        @foreach ($cards as $order)
                            <a class="ui-card block p-4" href="{{ route('office.orders.show', $order) }}">
                                <p class="text-[13px] text-slate-500">#{{ $order->order_id }}</p>
                                <p class="mt-1 font-medium">{{ $order->title }}</p>
                                <p class="mt-2"><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <form method="POST" action="{{ route('office.orders.bulk') }}" class="ui-card mt-6 overflow-hidden">
            @csrf
            <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                <input name="note" placeholder="Internal note for the selected orders" class="ui-input">
                <button class="ui-btn ui-btn-primary h-12 shrink-0" type="submit">Add note</button>
            </div>
            <table class="w-full text-left text-[15px]">
                <thead class="text-[13px] text-slate-500"><tr>
                    <th class="px-4 py-3"></th><th class="px-4 py-3">ID</th><th class="px-4 py-3">Title</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Deadline</th><th class="px-4 py-3">Total</th>
                </tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="px-4"><input type="checkbox" name="ids[]" value="{{ $order->order_id }}" aria-label="Select {{ $order->order_id }}"></td>
                            <td class="px-4 py-3 tabular-nums"><a class="font-medium" href="{{ route('office.orders.show', $order) }}">#{{ $order->order_id }}</a></td>
                            <td class="px-4 py-3">{{ $order->title }}</td>
                            <td class="px-4 py-3"><x-pill :status="$order->status_id" :label="$order->statusLabel()" /></td>
                            <td class="px-4 py-3 text-slate-500">{{ $order->customer_deadline?->diffForHumans() ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">${{ number_format((float) $order->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-slate-500">Nothing matches.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </form>
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
@endsection
