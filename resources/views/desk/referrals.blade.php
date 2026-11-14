@extends('layouts.desk')
@section('title', 'Referrals — '.$siteName)
@section('heading', 'Referrals')

@section('content')
    <div class="max-w-[720px]">
        <p class="text-slate-500">Share this link. When that person creates an account, they are recorded as referred by you. A ten-dollar credit is applied on their first paid order.</p>
        <div class="ui-card mt-6 flex items-center justify-between gap-4 p-5" x-data="{ copied: false }">
            <p class="min-w-0 break-all text-[15px]">{{ $link }}</p>
            <button class="ui-btn ui-btn-quiet h-10 shrink-0" type="button" @click="navigator.clipboard.writeText(@js($link)); copied = true"> <span x-text="copied ? 'Copied' : 'Copy'"></span></button>
        </div>
        <p class="mt-8 text-[13px] text-slate-500">Referral balance</p>
        <p class="font-display text-[32px] tabular-nums tracking-tight">${{ number_format((float) $customer->affiliate_balance, 2) }}</p>
        <ul class="ui-card mt-4 divide-y divide-slate-100">
            @forelse ($referrals as $referral)
                <li class="px-5 py-4 text-[15px]">Customer #{{ $referral->referree_id }} · <span class="tabular-nums">${{ number_format((float) $referral->amount, 2) }}</span></li>
            @empty
                <li class="px-5 py-12 text-center">
                    <x-glyph name="users" class="mx-auto size-8 text-slate-300" />
                    <p class="mt-3 font-display text-xl text-navy">No referrals yet</p>
                </li>
            @endforelse
        </ul>
    </div>
@endsection
