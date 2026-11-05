@extends('layouts.site')
@section('title', 'Create an account — '.$siteName)

@section('content')
    <section class="mx-auto max-w-md px-5 py-16">
        <h1 class="font-display text-4xl text-navy">Create an account</h1>
        <p class="mt-3 text-sm leading-6 text-ink/70">If you already order from the studio, sign in instead. A new account is only saved when this site is allowed to write to the customer list.</p>
        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4 rounded-3xl bg-white p-7">
            @csrf
            <div>
                <label class="text-sm font-medium" for="name">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium" for="phone">Phone <span class="font-normal text-ink/50">(optional)</span></label>
                <input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium" for="password">Password</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="terms" value="1" required class="mt-1">
                <span>I agree to the <a class="underline" href="{{ route('legal', 'terms') }}">terms</a>, including the rule against student submissions.</span>
            </label>
            @if ($errors->any())
                <p class="text-sm text-red-800" role="alert">{{ $errors->first() }}</p>
            @endif
            <button class="w-full rounded-full bg-navy px-5 py-3 text-sm font-semibold text-white" type="submit">Create account</button>
        </form>
    </section>
@endsection
