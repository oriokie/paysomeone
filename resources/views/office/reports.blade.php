@extends('layouts.office')
@section('heading', 'Reports')
@section('content')
    <dl class="grid gap-4 sm:grid-cols-3">
        <div class="ui-card p-6"><dt class="text-[13px] text-slate-500">Paid in</dt><dd class="mt-2 font-display text-[32px] tabular-nums">${{ number_format($revenue, 2) }}</dd></div>
        <div class="ui-card p-6"><dt class="text-[13px] text-slate-500">Orders</dt><dd class="mt-2 font-display text-[32px] tabular-nums">{{ $orders }}</dd></div>
        <div class="ui-card p-6"><dt class="text-[13px] text-slate-500">Writer pay booked</dt><dd class="mt-2 font-display text-[32px] tabular-nums">${{ number_format($writerPay, 2) }}</dd></div>
    </dl>
    <a class="ui-btn ui-btn-primary mt-6" href="{{ route('office.reports.export') }}">Download orders CSV</a>
@endsection
