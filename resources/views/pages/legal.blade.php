@extends('layouts.site')
@section('title', $heading.' — '.$siteName)
@section('description', $heading.' for '.$siteName.'.')

@section('content')
    <article class="wrap max-w-3xl py-16 lg:py-20">
        <p class="eyebrow">Legal</p>
        <h1 class="mt-3 font-display text-5xl text-navy">{{ $heading }}</h1>
        <div class="prose mt-8">
            @if ($page === 'privacy')
                <p>We keep the account details the studio already holds: your name, email, phone if you gave one, orders, files, and messages. This new site reads that information so you can sign in. It does not sell it, and it does not send it to a software vendor for a licence check.</p>
                <p>On this preview, a contact form or a project brief is stored in tables that belong only to the new site. While the read-only switch is on, your existing orders and password are not changed.</p>
                <p>Password reset links, when email is switched on, expire after 60 minutes. The link itself is not stored in a readable form.</p>
                <p>Staff sign-in is limited to administrators. There is no address on this site that signs someone in without a password.</p>
            @elseif ($page === 'terms')
                <p>These terms cover writing for businesses and professionals: articles, editing, proofreading, rewriting, business documents, and resumes.</p>
                <p>You confirm that you will not ask us to produce work for a student to hand in as their own, including essays, coursework, dissertations, or exam answers. We decline that work.</p>
                <p>You are responsible for the facts you supply, including employment history on a resume. We do not invent credentials.</p>
                <p>A quoted price follows the rate card and the options you select. A price on this preview is an estimate from that card. It is not an invoice while safe mode is on.</p>
                <p>The finished draft is prepared for you. How you publish it, and any byline, is yours to decide within the law.</p>
            @else
                <p>If a paid project is not what was briefed, tell the studio before you approve it. A revision that stays inside the original brief is the first remedy.</p>
                <p>This preview does not charge you while safe mode is on, so it also does not refund you. Work that was paid on the current site stays under that site’s refund practice until the cut-over.</p>
                <p>Coupons and wallet balances already on your account are not spent by browsing this preview.</p>
            @endif
            <p class="text-sm text-muted">This is a plain-language summary for the new site, not a lawyer’s opinion. Edwin can replace it with counsel’s wording before the public launch.</p>
        </div>
    </article>
@endsection
