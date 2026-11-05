@extends('layouts.site')
@section('title', 'FAQ — '.$siteName)
@section('description', 'Who the studio writes for, how pricing works, and what happens to existing accounts during the preview.')

@section('content')
    <section class="wrap max-w-3xl py-16 lg:py-20">
        <p class="eyebrow">FAQ</p>
        <h1 class="mt-3 font-display text-5xl text-navy">Questions worth answering first</h1>
        <div class="mt-10 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-paper-raised">
            @foreach ([
                ['Who is this for?', 'Businesses and professionals who need articles, editing, rewriting, documents, or a resume. If a university would call the work an assignment, we will not take it.'],
                ['Can I sign in with my current password?', 'Yes. The new site checks the password already stored for your account, including older formats, and can replace it with a modern hash that the current site still accepts.'],
                ['Will this preview change my orders?', 'Not while the read-only switch is on. That is the default. Looking around, signing in, and calculating a price do not alter the live order book.'],
                ['How is the price calculated?', 'From the rate card already in the database. Articles are per word. Other work is per page, doubled for single spacing. Extras are the percentages and flat fees the studio already uses.'],
                ['Do you take payment here?', 'The connections exist for M-Pesa, PayPal, Stripe, and Pesapal, and they stay silent while safe mode is on. This preview will not charge a card or a phone.'],
                ['Where do messages and files go?', 'They are on your desk, stored in the same records as the current site. While this preview is read-only, you can read them and not change them.'],
                ['Can a coupon be used?', 'Percent, fixed-amount, and per-rate coupons from the existing coupon list can be tried on the order form. A code that is expired, switched off, or already used is refused.'],
            ] as [$q, $a])
                <details class="group px-6 py-5">
                    <summary class="cursor-pointer list-none font-medium text-navy marker:content-none [&::-webkit-details-marker]:hidden">
                        <span class="flex items-center justify-between gap-4">{{ $q }}<span class="text-muted transition group-open:rotate-45" aria-hidden="true">+</span></span>
                    </summary>
                    <p class="mt-3 leading-7 text-muted">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>
@endsection
