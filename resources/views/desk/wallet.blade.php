@extends('layouts.desk')
@section('title', 'Wallet — '.$siteName)
@section('heading', 'Wallet')

@section('content')
    <div class="ui-card max-w-xl bg-navy p-8 text-white">
        <p class="text-[13px] text-white/60">Available balance</p>
        <p class="mt-2 font-display text-[32px] tabular-nums tracking-tight">${{ number_format((float) $customer->wallet_balance, 2) }}</p>
        <p class="mt-3 max-w-md text-[13px] leading-5 text-white/60">This is the balance on your customer record. Paying an order from the wallet writes a transaction the current site already understands.</p>
    </div>

    <h2 class="mt-10 font-display text-2xl tracking-tight text-navy">Transactions</h2>
    <div class="ui-card mt-4 overflow-hidden">
        <table class="w-full text-left text-[15px]">
            <thead class="text-[13px] text-slate-500">
                <tr><th class="px-5 py-3 font-medium">When</th><th class="px-5 py-3 font-medium">Method</th><th class="px-5 py-3 font-medium">Amount</th></tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-4 text-slate-600">{{ $transaction->date_add }}</td>
                        <td class="px-5 py-4">{{ $transaction->payment_method }} · {{ $transaction->source }}</td>
                        <td class="px-5 py-4 tabular-nums {{ in_array($transaction->source, ['deposit', 'refund'], true) ? 'text-good' : '' }}">{{ in_array($transaction->source, ['deposit', 'refund'], true) ? '+' : '−' }}${{ number_format((float) $transaction->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-12 text-slate-500">No wallet transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $transactions->links() }}</div>

    <h2 class="mt-10 font-display text-2xl tracking-tight text-navy">Tip a writer</h2>
    @if ($tipOrder)
        <form class="ui-card mt-4 flex max-w-xl flex-wrap items-end gap-3 p-6" method="POST" action="{{ route('desk.tip.store', $tipOrder) }}">
            @csrf
            <div>
                <p class="text-[13px] text-slate-500">From order #{{ $tipOrder->order_id }}</p>
                <label class="ui-label mt-3" for="tip-amount">Amount</label>
                <input id="tip-amount" name="amount" type="number" min="1" step="1" value="5" class="ui-input w-32">
            </div>
            <button class="ui-btn ui-btn-primary" type="submit">Send tip from wallet</button>
        </form>
    @else
        <p class="mt-3 max-w-xl text-[15px] text-slate-500">Tips are sent from a delivered or finished order. Nothing is ready to tip yet.</p>
    @endif
@endsection
