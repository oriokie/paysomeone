@extends('layouts.office')
@section('title', 'Earnings — '.$siteName)
@section('heading', 'Earnings')

@section('content')
    <dl class="grid gap-4 sm:grid-cols-3">
        <div class="ui-card bg-navy p-6 text-white"><dt class="text-[13px] text-white/60">Finished writer pay</dt><dd class="mt-2 font-display text-[32px] tabular-nums">${{ number_format($earned, 2) }}</dd></div>
        <div class="ui-card p-6"><dt class="text-[13px] text-slate-500">Fines</dt><dd class="mt-2 font-display text-[32px] tabular-nums">${{ number_format($fines, 2) }}</dd></div>
        <div class="ui-card p-6"><dt class="text-[13px] text-slate-500">Credits</dt><dd class="mt-2 font-display text-[32px] tabular-nums">${{ number_format($credits, 2) }}</dd></div>
    </dl>
    @if ($safeMode)
        <p class="mt-4 rounded-2xl bg-wait-soft px-4 py-3 text-[13px] text-wait">Safe mode is on. A payout request is stored, and M-Pesa is not contacted.</p>
    @endif
    @if (\App\Services\Office\Access::allows($staff, 'payouts.request'))
        <form class="ui-card mt-6 grid gap-4 p-6 sm:grid-cols-[1fr_1fr_auto]" method="POST" action="{{ route('office.earnings.request') }}">
            @csrf
            <div><label class="ui-label" for="phone">M-Pesa phone</label><input id="phone" name="phone" required class="ui-input" placeholder="07…"></div>
            <div><label class="ui-label" for="amount">Amount</label><input id="amount" name="amount" type="number" min="1" step="0.01" required class="ui-input"></div>
            <button class="ui-btn ui-btn-primary self-end" type="submit">Request payout</button>
        </form>
    @endif
    <h2 class="mt-8 font-display text-xl tracking-tight">Requests</h2>
    <ul class="ui-card mt-3 divide-y divide-slate-100">
        @forelse ($requests as $request)
            <li class="flex items-center justify-between px-5 py-3 text-[13px]">
                <span>#{{ $request->payment_request_id }} · {{ $writers[$request->user_id]?->getFilamentName() ?? 'Writer' }} · ${{ number_format((float) $request->total_pay, 2) }} · {{ $request->status ? 'Paid' : 'Pending' }}</span>
                @if (\App\Services\Office\Access::allows($staff, 'payouts.approve') && ! $request->status)
                    <form method="POST" action="{{ route('office.payouts.approve', $request) }}">@csrf<button class="font-medium text-navy" type="submit">Approve</button></form>
                @endif
            </li>
        @empty
            <li class="px-5 py-8 text-[13px] text-slate-500">No payout requests yet.</li>
        @endforelse
    </ul>
    <h2 class="mt-8 font-display text-xl tracking-tight">Finished orders</h2>
    <ul class="ui-card mt-3 divide-y divide-slate-100 text-[13px]">
        @foreach ($orders as $order)
            <li class="flex justify-between px-5 py-3"><span>#{{ $order->order_id }} {{ $order->title }}</span><span class="tabular-nums">${{ number_format((float) $order->writer_pay, 2) }}</span></li>
        @endforeach
    </ul>
@endsection
