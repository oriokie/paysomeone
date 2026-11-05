@extends('layouts.site')
@section('title', 'Contact — '.$siteName)
@section('description', 'Write to the studio about a business article, an edit, a rewrite, a document, or a resume.')

@section('content')
    <section class="wrap grid gap-12 py-16 lg:grid-cols-2 lg:py-20">
        <div>
            <p class="eyebrow">Contact</p>
            <h1 class="mt-3 font-display text-5xl text-navy">Tell us what you need.</h1>
            <p class="lede mt-4">A business article, an edit, a rewrite, a document, or a resume. If you already have an account, you can also start a priced brief.</p>
            <a href="{{ route('order.create') }}" class="mt-6 inline-flex text-sm font-semibold text-accent">Use the order form instead</a>
        </div>
        <form method="POST" action="{{ route('contact.store') }}" class="card space-y-4 p-7" novalidate>
            @csrf
            <div><label class="label" for="name">Name</label><input id="name" name="name" value="{{ old('name') }}" required class="field" autocomplete="name"></div>
            <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required class="field" autocomplete="email"></div>
            <div><label class="label" for="company">Company <span class="font-normal text-muted">(optional)</span></label><input id="company" name="company" value="{{ old('company') }}" class="field" autocomplete="organization"></div>
            <div><label class="label" for="message">Message</label><textarea id="message" name="message" required minlength="20" rows="6" class="field">{{ old('message') }}</textarea></div>
            @if ($errors->any())<p class="text-sm text-bad" role="alert">{{ $errors->first() }}</p>@endif
            <button class="btn btn-primary" type="submit">Send</button>
        </form>
    </section>
@endsection
