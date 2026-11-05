@extends('layouts.site')
@section('title', 'FAQ — '.$siteName)
@section('description', 'Who the studio writes for, how pricing works, and what happens to existing accounts during the preview.')

@section('content')
    <section class="mx-auto max-w-3xl px-5 py-16">
        <h1 class="font-display text-4xl text-navy sm:text-5xl">Questions worth answering first</h1>
        <div class="mt-10 divide-y divide-line rounded-3xl border border-line bg-white">
            @foreach ([
                ['Who is this for?', 'Businesses and professionals who need articles, editing, rewriting, documents, or a resume. If a university would call the work an assignment, we will not take it.'],
                ['Can I sign in with my current password?', 'Yes. The new site checks the password already stored for your account, including older formats, and can replace it with a modern hash that the current site still accepts.'],
                ['Will this preview change my orders?', 'Not while the read-only switch is on. That is the default. Looking around, signing in, and calculating a price do not alter the live order book.'],
                ['How is the price calculated?', 'From the rate card already in the database. Articles are per word. Other work is per page, doubled for single spacing. Extras are the percentages and flat fees the studio already uses. Open the order form to see a live total.'],
                ['Do you take payment here?', 'Not yet. M-Pesa, PayPal, Stripe, and Pesapal are the next release. This preview will not charge a card or send a payment request.'],
                ['Where do messages and files go?', 'They stay on the current website until the customer desk is built. Signing in here shows your name, your wallet balance, and how many orders are on the account. It does not replace that desk yet.'],
                ['Can a coupon be used?', 'Percent, fixed-amount, and per-rate coupons from the existing coupon list can be tried on the order form. A code that is expired, switched off, or already used is refused.'],
            ] as [$q, $a])
                <details class="group px-6 py-5">
                    <summary class="cursor-pointer font-medium text-navy">{{ $q }}</summary>
                    <p class="mt-3 leading-7 text-ink/75">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>
@endsection
