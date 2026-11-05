@extends('layouts.checkout')
@section('title', 'Office sign-in — '.$siteName)
@section('content')
    <section class="mx-auto max-w-[420px] px-5 py-16">
        <p class="text-[13px] font-medium text-slate-500">Office</p>
        <h1 class="mt-2 font-display text-[32px] tracking-tight text-navy">Staff sign-in</h1>
        <p class="mt-3 text-[15px] text-slate-500">Use the email and password already on your staff record. Two-factor is required before the office opens.</p>
        <form class="ui-card mt-8 space-y-4 p-6" method="POST" action="{{ route('office.login') }}">
            @csrf
            <div>
                <label class="ui-label" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="ui-input" autocomplete="username">
            </div>
            <div>
                <label class="ui-label" for="password">Password</label>
                <input id="password" name="password" type="password" required class="ui-input" autocomplete="current-password">
            </div>
            @if ($errors->any())<p class="text-[13px] text-bad" role="alert">{{ $errors->first() }}</p>@endif
            <button class="ui-btn ui-btn-primary w-full" type="submit">Continue</button>
        </form>
        <p class="mt-4 text-center text-[13px]"><a class="underline" href="{{ route('office.forgot') }}">Forgot your password?</a></p>
    </section>
@endsection
