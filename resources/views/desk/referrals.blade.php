@extends('layouts.desk')
@section('title', 'Referrals — '.$siteName)
@section('content')
    <h1 class="font-display text-4xl text-navy">Referrals</h1>
    <p class="mt-3 max-w-2xl leading-7 text-muted">Share this link. When that person creates an account, they are recorded as referred by you. A ten-dollar credit is applied on their first paid order.</p>
    <p class="card mt-6 break-all px-4 py-3 text-sm">{{ $link }}</p>
    <p class="mt-6 text-sm">Referral balance <span class="font-display text-2xl text-navy">${{ number_format((float) $customer->affiliate_balance, 2) }}</span></p>
    <ul class="card mt-4 divide-y divide-line">
        @forelse ($referrals as $referral)
            <li class="px-5 py-3 text-sm">Customer #{{ $referral->referree_id }} · ${{ number_format((float) $referral->amount, 2) }}</li>
        @empty
            <li class="px-5 py-8 text-sm text-muted">No referrals yet.</li>
        @endforelse
    </ul>
@endsection
