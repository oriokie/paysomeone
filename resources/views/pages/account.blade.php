@extends('layouts.site')
@section('title', 'Your account — '.$siteName)

@section('content')
    <section class="mx-auto max-w-3xl px-5 py-16">
        <h1 class="font-display text-4xl text-navy">Hello, {{ $customer->name ?: 'there' }}.</h1>
        <p class="mt-4 leading-7 text-ink/75">You are signed in with the account the studio already has. Orders, files, messages, and revisions stay on the current website until the customer desk is ready here.</p>
        <dl class="mt-10 grid gap-4 sm:grid-cols-3">
            <div class="rounded-3xl bg-white p-5">
                <dt class="text-xs tracking-wide text-ink/50 uppercase">Email</dt>
                <dd class="mt-2 break-all font-medium">{{ $customer->email }}</dd>
            </div>
            <div class="rounded-3xl bg-white p-5">
                <dt class="text-xs tracking-wide text-ink/50 uppercase">Wallet</dt>
                <dd class="mt-2 font-medium">${{ number_format((float) $customer->wallet_balance, 2) }}</dd>
            </div>
            <div class="rounded-3xl bg-white p-5">
                <dt class="text-xs tracking-wide text-ink/50 uppercase">Orders on file</dt>
                <dd class="mt-2 font-medium">{{ $customer->orders_count }}</dd>
            </div>
        </dl>
        <a href="{{ route('order.create') }}" class="mt-8 inline-block rounded-full bg-navy px-5 py-3 text-sm font-semibold text-white">Price a new brief</a>
    </section>
@endsection
