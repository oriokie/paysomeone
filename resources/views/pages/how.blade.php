@extends('layouts.site')
@section('title', 'How it works — '.$siteName)
@section('description', 'Brief, price, match a writer, review the draft. The same order book as the current site, with a calmer front door.')

@section('content')
    <section class="wrap max-w-3xl py-16 lg:py-20">
        <p class="eyebrow">How it works</p>
        <h1 class="mt-3 font-display text-5xl text-navy">A short path from brief to draft.</h1>
        <ol class="mt-14 space-y-10">
            @foreach ([
                ['Price it before you commit', 'The order form reads the live rate card: turnaround, length, spacing, writer tier, and the extras you tick. Nothing is invented in the browser.'],
                ['Send the brief', 'On this preview the brief is not written into the live order book. When writes are allowed, a signed-in customer’s order is filed in the same list the studio already uses.'],
                ['A writer is matched', 'Best available, senior specialist, or native English. Those choices map to the tiers and percentages already in use.'],
                ['You review the work', 'Files, messages, and revision requests are on your desk. Until the cut-over, the current website remains the system of record while this preview is read-only.'],
                ['You approve', 'M-Pesa, PayPal, Stripe, and Pesapal are connected and stay silent while safe mode is on. This preview does not charge a card or a phone.'],
            ] as $index => [$title, $body])
                <li class="grid grid-cols-[auto_1fr] gap-5">
                    <span class="font-display text-3xl text-accent">0{{ $index + 1 }}</span>
                    <div>
                        <h2 class="font-display text-2xl text-navy">{{ $title }}</h2>
                        <p class="mt-2 leading-7 text-muted">{{ $body }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endsection
