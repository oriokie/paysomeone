@extends('layouts.site')
@section('title', 'Create an account — '.$siteName)

@section('content')
    <section class="wrap flex max-w-md flex-col py-16 lg:py-24">
        <p class="eyebrow">Account</p>
        <h1 class="mt-3 font-display text-4xl text-navy">Create an account</h1>
        <p class="mt-3 text-sm leading-6 text-muted">If you already order from the studio, sign in instead. A new account is only saved when this site is allowed to write to the customer list.</p>
        <form method="POST" action="{{ route('register') }}" class="card mt-8 space-y-4 p-7">
            @csrf
            @if (request('ref'))<input type="hidden" name="ref" value="{{ request('ref') }}">@endif
            <div><label class="label" for="name">Name</label><input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="field"></div>
            <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="field"></div>
            <div><label class="label" for="phone">Phone <span class="font-normal text-muted">(optional)</span></label><input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" class="field"></div>
            <div><label class="label" for="password">Password</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="field"></div>
            <div><label class="label" for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" class="field"></div>
            <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="terms" value="1" required class="mt-1"><span>I agree to the <a class="font-semibold text-accent" href="{{ route('legal', 'terms') }}">terms</a>, including the rule against student submissions.</span></label>
            @if ($errors->any())<p class="text-sm text-bad" role="alert">{{ $errors->first() }}</p>@endif
            <button class="btn btn-primary w-full" type="submit">Create account</button>
        </form>
    </section>
@endsection
