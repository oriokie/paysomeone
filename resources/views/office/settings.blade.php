@extends('layouts.office')
@section('heading', 'Settings')
@section('content')
    <form class="ui-card max-w-xl space-y-4 p-6" method="POST" action="{{ route('office.settings.save') }}">
        @csrf
        <div>
            <label class="ui-label" for="subadmin_refund_limit">Sub-admin cancellation limit (USD)</label>
            <input id="subadmin_refund_limit" name="subadmin_refund_limit" type="number" min="0" step="1" value="{{ $refundLimit }}" class="ui-input">
            <p class="ui-hint">A sub-admin cannot cancel an order above this total.</p>
        </div>
        <div>
            <label class="ui-label" for="session_minutes">Session timeout (minutes)</label>
            <input id="session_minutes" name="session_minutes" type="number" min="5" max="240" value="{{ $sessionMinutes }}" class="ui-input">
        </div>
        <button class="ui-btn ui-btn-primary" type="submit">Save</button>
    </form>
    <form class="ui-card mt-6 max-w-xl space-y-3 p-6" method="POST" action="{{ route('office.settings.test-email') }}">
        @csrf
        <h2 class="font-display text-xl tracking-tight">Test email</h2>
        <p class="text-[13px] text-slate-500">Sends one message to {{ $staff->email }}. Safe mode records the attempt and does not leave the server.</p>
        <button class="ui-btn ui-btn-quiet" type="submit">Send a test email</button>
    </form>
    <h2 class="mt-10 font-display text-xl tracking-tight">Gateways</h2>
    <p class="mt-1 text-[13px] text-slate-500">Keys stay in the environment file. Only the last four characters are shown.</p>
    <ul class="ui-card mt-3 divide-y divide-slate-100">
        @foreach ($gateways as $gateway)
            <li class="flex items-center justify-between px-5 py-3 text-[13px]"><span>{{ $gateway['name'] }}</span><span class="font-mono">{{ $gateway['configured'] ? $gateway['hint'] : 'Not set' }}</span></li>
        @endforeach
    </ul>
    <h2 class="mt-10 font-display text-xl tracking-tight">Rate card</h2>
    <ul class="ui-card mt-3 divide-y divide-slate-100 text-[13px]">
        @foreach ($deadlines as $deadline)
            <li class="flex justify-between px-5 py-3"><span>{{ $deadline->name }}</span><span class="tabular-nums">${{ number_format((float) $deadline->price_per_page, 4) }}</span></li>
        @endforeach
    </ul>
    <h2 class="mt-10 font-display text-xl tracking-tight">Coupons</h2>
    <ul class="ui-card mt-3 divide-y divide-slate-100 text-[13px]">
        @foreach ($coupons as $coupon)
            <li class="flex justify-between px-5 py-3"><span>{{ $coupon->coupon_code }}</span><span>{{ $coupon->active ? 'Active' : 'Off' }}</span></li>
        @endforeach
    </ul>
@endsection
