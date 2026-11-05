@extends('layouts.site')
@section('title', 'About — '.$siteName)
@section('description', 'PaySomeoneToWrite is being rebuilt for businesses and professionals, on the same order book the studio already runs.')

@section('content')
    <section class="wrap max-w-3xl py-16 lg:py-20">
        <p class="eyebrow">About</p>
        <h1 class="mt-3 font-display text-5xl text-navy">The same studio. A front door that matches the work.</h1>
        <div class="mt-8 space-y-5 text-lg leading-8 text-muted">
            <p>{{ $siteName }} is a professional writing service. Companies and individuals use it for articles, editing, proofreading, rewriting, business documents, and resumes.</p>
            <p>Edwin bought the existing order system and is replacing the public site in place. Customer accounts, orders, writers, prices, and payments stay in the database that is already live. This new site can run on a subdomain while the current site keeps serving customers.</p>
            <p>The rebuild drops what does not belong: no academic or student assignments, no remote updater, no licence check, and no way for anyone to sign in as staff without a password.</p>
        </div>
    </section>
@endsection
