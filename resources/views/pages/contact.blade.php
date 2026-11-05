@extends('layouts.site')
@section('title', 'Contact — '.$siteName)
@section('description', 'Write to the studio about a business article, an edit, a rewrite, a document, or a resume.')

@section('content')
    <section class="mx-auto grid max-w-6xl gap-12 px-5 py-16 lg:grid-cols-2">
        <div>
            <h1 class="font-display text-4xl text-navy">Contact</h1>
            <p class="mt-4 max-w-md leading-7 text-ink/75">Tell us what you need written or edited. If you already have an account, you can also start a priced brief.</p>
            <a href="{{ route('order.create') }}" class="mt-6 inline-block text-sm font-medium text-navy underline decoration-brass underline-offset-4">Use the order form instead</a>
        </div>
        <form method="POST" action="{{ route('contact.store') }}" class="space-y-4 rounded-3xl bg-white p-7" novalidate>
            @csrf
            <div>
                <label class="text-sm font-medium" for="name">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-line px-3 py-2" autocomplete="name">
            </div>
            <div>
                <label class="text-sm font-medium" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-line px-3 py-2" autocomplete="email">
            </div>
            <div>
                <label class="text-sm font-medium" for="company">Company <span class="font-normal text-ink/50">(optional)</span></label>
                <input id="company" name="company" value="{{ old('company') }}" class="mt-1 w-full rounded-xl border border-line px-3 py-2" autocomplete="organization">
            </div>
            <div>
                <label class="text-sm font-medium" for="message">Message</label>
                <textarea id="message" name="message" required minlength="20" rows="6" class="mt-1 w-full rounded-xl border border-line px-3 py-2">{{ old('message') }}</textarea>
            </div>
            @if ($errors->any())
                <p class="text-sm text-red-800" role="alert">{{ $errors->first() }}</p>
            @endif
            <button class="rounded-full bg-navy px-5 py-3 text-sm font-semibold text-white" type="submit">Send</button>
        </form>
    </section>
@endsection
