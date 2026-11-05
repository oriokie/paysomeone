@extends('layouts.site')
@section('title', 'Referrals — '.$siteName)
@section('content')
    @include('desk.nav')
    <section class="mx-auto max-w-3xl px-5 py-12">
        <h1 class="font-display text-4xl text-navy">Referrals</h1>
        <p class="mt-3 leading-7 text-ink/70">Share this link. When that person creates an account, they are recorded as referred by you. A referral credit is applied on their first paid order, the same way the current site does it once payments are switched on.</p>
        <p class="mt-4 break-all rounded-2xl bg-white px-4 py-3 text-sm">{{ $link }}</p>
        <p class="mt-6 text-sm">Referral balance: ${{ number_format((float) $customer->affiliate_balance, 2) }}</p>
        <ul class="mt-4 divide-y divide-line rounded-3xl border border-line bg-white">
            @forelse ($referrals as $referral)
                <li class="px-5 py-3 text-sm">Customer #{{ $referral->referree_id }} · ${{ number_format((float) $referral->amount, 2) }}</li>
            @empty
                <li class="px-5 py-6 text-sm text-ink/60">No referrals yet.</li>
            @endforelse
        </ul>
    </section>
@endsection
