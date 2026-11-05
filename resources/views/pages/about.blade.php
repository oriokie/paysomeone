@extends('layouts.site')
@section('title', 'About — '.$siteName)
@section('description', 'PaySomeoneToWrite is being rebuilt for businesses and professionals, on the same order book the studio already runs.')

@section('content')
    <section class="mx-auto max-w-3xl px-5 py-16">
        <p class="text-sm font-medium tracking-[0.16em] text-brass uppercase">About</p>
        <h1 class="mt-4 font-display text-4xl text-navy sm:text-5xl">The same studio. A front door that matches the work.</h1>
        <div class="mt-8 space-y-5 text-lg leading-8 text-ink/80">
            <p>{{ $siteName }} is a professional writing service. Companies and individuals use it for articles, editing, proofreading, rewriting, business documents, and resumes.</p>
            <p>Edwin bought the existing order system and is replacing the public site in place. Customer accounts, orders, writers, prices, and payments stay in the database that is already live. This new site can run on a subdomain while the current site keeps serving customers.</p>
            <p>The rebuild drops the parts that do not belong: no academic or student assignments, no remote updater, no licence check, and no way for anyone to sign in as staff without a password.</p>
            <p>Writers are matched from the team the studio already has. Their pay, bids, and M-Pesa payouts stay in the same records and will be managed from the staff office in a later release.</p>
        </div>
    </section>
@endsection
