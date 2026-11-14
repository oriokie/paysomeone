@extends('layouts.desk')
@section('title', 'Payments — '.$siteName)
@section('heading', 'Payments')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[13px] text-slate-500">Wallet balance</p>
            <p class="font-display text-[32px] tabular-nums tracking-tight text-navy">${{ number_format((float) $customer->wallet_balance, 2) }}</p>
        </div>
        <a class="ui-btn ui-btn-quiet h-10" href="{{ route('desk.wallet') }}">Wallet</a>
    </div>

    <form class="ui-card mt-6 grid gap-3 p-4 md:grid-cols-[1fr_10rem_10rem_8rem_auto]" method="GET">
        <div>
            <label class="ui-label" for="q">Search</label>
            <input id="q" name="q" value="{{ $filters['q'] }}" placeholder="Reference, receipt, or order" class="ui-input">
        </div>
        <div>
            <label class="ui-label" for="provider">Method</label>
            <select id="provider" name="provider" class="ui-input">
                <option value="">Any</option>
                @foreach (['mpesa' => 'M-Pesa', 'paypal' => 'PayPal', 'stripe' => 'Card', 'pesapal' => 'Pesapal', 'wallet' => 'Wallet'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['provider'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="ui-label" for="status">Status</label>
            <select id="status" name="status" class="ui-input">
                <option value="">Any</option>
                @foreach (['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="ui-label" for="per_page">Per page</label>
            <select id="per_page" name="per_page" class="ui-input">
                @foreach ([10, 25, 50] as $size)
                    <option value="{{ $size }}" @selected((int) $filters['per_page'] === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <button class="ui-btn ui-btn-primary h-12 self-end" type="submit">Filter</button>
    </form>

    <div class="mt-4 space-y-3 md:hidden">
        @forelse ($payments as $payment)
            <a class="ui-card block p-4" href="{{ route('desk.payments.show', $payment) }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ ['mpesa' => 'M-Pesa', 'paypal' => 'PayPal', 'stripe' => 'Card', 'pesapal' => 'Pesapal', 'wallet' => 'Wallet'][$payment->provider] ?? $payment->provider }}</p>
                        <p class="mt-1 text-[13px] text-slate-500">{{ $payment->created_at?->timezone(config('app.timezone'))->format('j M Y, H:i') }}</p>
                    </div>
                    @include('desk.payment-badge', ['status' => $payment->status])
                </div>
                <p class="mt-3 font-display text-2xl tabular-nums">${{ number_format((float) $payment->amount, 2) }}</p>
            </a>
        @empty
            <p class="ui-card px-5 py-12 text-center text-slate-500">No payments match these filters.</p>
        @endforelse
    </div>
    <div class="ui-card mt-4 hidden overflow-hidden md:block">
        <table class="w-full text-left text-[15px]">
            <thead class="text-[13px] text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-medium">When</th>
                    <th class="px-5 py-3 font-medium">Method</th>
                    <th class="px-5 py-3 font-medium">Order</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 text-right font-medium">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-4 text-slate-600">{{ $payment->created_at?->timezone(config('app.timezone'))->format('j M Y, H:i') }}</td>
                        <td class="px-5 py-4">{{ ['mpesa' => 'M-Pesa', 'paypal' => 'PayPal', 'stripe' => 'Card', 'pesapal' => 'Pesapal', 'wallet' => 'Wallet'][$payment->provider] ?? $payment->provider }}</td>
                        <td class="px-5 py-4">@if ($payment->order_id)<a class="font-medium text-navy" href="{{ route('desk.order', $payment->order_id) }}">#{{ $payment->order_id }}</a>@else — @endif</td>
                        <td class="px-5 py-4"><a href="{{ route('desk.payments.show', $payment) }}">@include('desk.payment-badge', ['status' => $payment->status])</a></td>
                        <td class="px-5 py-4 text-right tabular-nums">${{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-16 text-center text-slate-500">No payments match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
