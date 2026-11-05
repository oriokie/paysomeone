@extends('layouts.desk')
@section('title', 'Wallet — '.$siteName)
@section('content')
    <h1 class="font-display text-4xl text-navy">Wallet</h1>
    <p class="mt-4 font-display text-5xl text-navy">${{ number_format((float) $customer->wallet_balance, 2) }}</p>
    <p class="mt-2 max-w-xl text-sm text-muted">This is the balance stored on your customer record. Paying an order from the wallet writes a transaction the current site already understands.</p>
    <div class="card mt-8 overflow-hidden">
        <table class="data-table">
            <thead><tr><th>When</th><th>Method</th><th>Amount</th></tr></thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->date_add }}</td>
                        <td>{{ $transaction->payment_method }} · {{ $transaction->source }}</td>
                        <td>${{ number_format((float) $transaction->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-10 text-muted">No wallet transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
