@extends('layouts.desk')
@section('title', 'Payment — '.$siteName)
@section('heading', 'Payment')

@section('content')
    <a class="text-[13px] font-medium text-navy" href="{{ route('desk.payments') }}">All payments</a>
    <div class="ui-card mt-4 max-w-xl p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[13px] text-slate-500">{{ ['mpesa' => 'M-Pesa', 'paypal' => 'PayPal', 'stripe' => 'Card', 'pesapal' => 'Pesapal', 'wallet' => 'Wallet'][$payment->provider] ?? $payment->provider }}</p>
                <p class="mt-1 font-display text-[32px] tabular-nums tracking-tight">${{ number_format((float) $payment->amount, 2) }}</p>
            </div>
            @include('desk.payment-badge', ['status' => $payment->status])
        </div>
        <dl class="mt-6 space-y-3 text-[15px]">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Reference</dt><dd class="text-right font-mono text-[13px]">{{ $payment->reference }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Receipt</dt><dd class="text-right">{{ $payment->transaction_id ?: 'Not issued yet' }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Order</dt><dd>@if ($payment->order)<a class="font-medium text-navy" href="{{ route('desk.order', $payment->order) }}">#{{ $payment->order->order_id }}</a>@else — @endif</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Started</dt><dd>{{ $payment->created_at?->timezone(config('app.timezone'))->format('j M Y, H:i') }}</dd></div>
        </dl>
        @if (! empty($payment->payload['reason']))
            <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-[15px] text-slate-600">{{ $payment->payload['reason'] }}</p>
        @endif
        @if ($payment->status === 'pending')
            <p class="mt-4 text-[15px] text-slate-500">This payment is still with the provider. The order updates when the confirmation arrives. Refreshing this page does not charge you again.</p>
        @elseif ($payment->status === 'paid')
            <p class="mt-4 text-[15px] text-good">This payment is recorded on the order. A second notice for the same receipt is ignored.</p>
        @elseif ($payment->status === 'refunded')
            <p class="mt-4 text-[15px] text-slate-600">The provider reported a refund to the original method. It was not added to the wallet.</p>
        @endif
    </div>
@endsection
