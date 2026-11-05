@extends('layouts.site')
@section('title', 'How it works — '.$siteName)
@section('description', 'Brief, price, match a writer, review the draft. The same order book as the current site, with a calmer front door.')

@section('content')
    <section class="mx-auto max-w-3xl px-5 py-16">
        <p class="text-sm font-medium tracking-[0.16em] text-brass uppercase">How it works</p>
        <h1 class="mt-4 font-display text-4xl text-navy sm:text-5xl">A short path from brief to draft.</h1>
        <ol class="mt-12 space-y-10">
            @foreach ([
                ['Price it before you commit', 'The order form reads the live rate card: turnaround, length, spacing, writer tier, and the extras you tick. Nothing is invented in the browser.'],
                ['Send the brief', 'On this preview the brief is stored beside the current order book. When the new site is allowed to write, a signed-in customer’s inquiry is filed in the same order list the studio already uses.'],
                ['A writer is matched', 'Best available, senior specialist, or native English. Those choices map to the tiers and percentages already in use.'],
                ['You review the work', 'Files, messages, and revision requests are the next release of this site. Until then, that part of the job stays on the current website.'],
                ['You approve', 'Payment by M-Pesa, PayPal, Stripe, or Pesapal is planned for the next release. This preview does not charge a card or a phone.'],
            ] as $index => [$title, $body])
                <li class="grid grid-cols-[auto_1fr] gap-5">
                    <span class="font-display text-3xl text-brass">0{{ $index + 1 }}</span>
                    <div>
                        <h2 class="font-display text-2xl text-navy">{{ $title }}</h2>
                        <p class="mt-2 leading-7 text-ink/75">{{ $body }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endsection
