@extends('layouts.office')
@section('title', 'Office — '.$siteName)
@section('heading', \App\Services\Office\Permissions::roleName((int) $staff->employee_group))

@section('content')
    <p class="font-display text-[32px] tracking-tight text-navy">Hello, {{ $staff->getFilamentName() }}.</p>
    <p class="mt-2 text-slate-500">Numbers below are counted from the live order book. Nothing here is a sample statistic.</p>
    <dl class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $label => $value)
            <div class="ui-card p-6">
                <dt class="text-[13px] text-slate-500">{{ $label }}</dt>
                <dd class="mt-2 font-display text-[32px] tabular-nums tracking-tight">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
    <section class="ui-card mt-8 p-6">
        <h2 class="font-display text-xl tracking-tight">Due within a day</h2>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($due as $order)
                <li class="flex items-center justify-between gap-3 py-3">
                    <a class="font-medium" href="{{ route('office.orders.show', $order) }}">#{{ $order->order_id }} {{ $order->title }}</a>
                    <span class="text-[13px] text-slate-500">{{ $order->writer_deadline?->diffForHumans() }}</span>
                </li>
            @empty
                <li class="py-4 text-[13px] text-slate-500">Nothing in your book is due in the next day.</li>
            @endforelse
        </ul>
    </section>
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="ui-card p-6">
            <h2 class="font-display text-xl tracking-tight">Recent orders</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($recent as $order)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <a class="font-medium" href="{{ route('office.orders.show', $order) }}">#{{ $order->order_id }} {{ $order->title }}</a>
                        <x-pill :status="$order->status_id" :label="$order->statusLabel()" />
                    </li>
                @empty
                    <li class="py-6 text-[13px] text-slate-500">No orders in your view yet.</li>
                @endforelse
            </ul>
        </section>
        <section class="ui-card p-6">
            <h2 class="font-display text-xl tracking-tight">Announcements</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($news as $item)
                    <li>
                        <p class="font-medium">{{ $item->title }}</p>
                        <p class="text-[13px] text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags((string) $item->content), 140) }}</p>
                    </li>
                @empty
                    <li class="text-[13px] text-slate-500">No announcements.</li>
                @endforelse
            </ul>
            <p class="mt-4 text-[13px] text-slate-500">Open payout requests you can see: {{ $payouts }}. Unread notices addressed to you: {{ $alerts }}.</p>
        </section>
    </div>
    @if ($fines->isNotEmpty())
        <section class="ui-card mt-6 p-6">
            <h2 class="font-display text-xl tracking-tight">Fines and credits</h2>
            <ul class="mt-3 divide-y divide-slate-100 text-[13px]">
                @foreach ($fines as $fine)
                    <li class="flex justify-between py-2"><span>{{ ucfirst($fine->type) }} · {{ \Illuminate\Support\Str::limit($fine->description, 80) }}</span><span class="tabular-nums">${{ number_format((float) $fine->amount, 2) }}</span></li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
