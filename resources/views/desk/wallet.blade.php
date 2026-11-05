@extends('layouts.site')
@section('title', 'Wallet — '.$siteName)
@section('content')
    @include('desk.nav')
    <section class="mx-auto max-w-3xl px-5 py-12">
        <h1 class="font-display text-4xl text-navy">Wallet</h1>
        <p class="mt-3 font-display text-5xl">${{ number_format((float) $customer->wallet_balance, 2) }}</p>
        <p class="mt-2 text-sm text-ink/60">This is the balance stored on your customer record. Paying an order from the wallet writes a transaction the current site already understands.</p>
        <ul class="mt-8 divide-y divide-line rounded-3xl border border-line bg-white">
            @forelse ($transactions as $transaction)
                <li class="flex justify-between px-5 py-3 text-sm">
                    <span>{{ $transaction->payment_method }} · {{ $transaction->source }} · {{ $transaction->transaction_id }}</span>
                    <span>${{ number_format((float) $transaction->amount, 2) }}</span>
                </li>
            @empty
                <li class="px-5 py-6 text-sm text-ink/60">No wallet transactions yet.</li>
            @endforelse
        </ul>
    </section>
@endsection
