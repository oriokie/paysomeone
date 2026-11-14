@extends('layouts.site')
@section('title', 'Sign in — '.$siteName)
@section('description', 'Sign in with the email and password you already use.')

@section('content')
    <section class="wrap flex max-w-md flex-col py-16 lg:py-24">
        <p class="eyebrow">Account</p>
        <h1 class="mt-3 font-display text-4xl text-navy">Sign in</h1>
        <p class="mt-3 text-sm leading-6 text-muted">Use the email and password on your current account. A password saved in an older format still works.</p>
        <form method="POST" action="{{ route('login') }}" class="card mt-8 space-y-4 p-7">
            @csrf
            <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field"></div>
            <div><label class="label" for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password" class="field"></div>
            @if ($errors->any())<p class="text-sm text-bad" role="alert">{{ $errors->first() }}</p>@endif
            <button class="btn btn-primary w-full" type="submit">Sign in</button>
            <p class="text-sm text-muted"><a class="font-semibold text-accent" href="{{ route('password.request') }}">Forgot your password?</a></p>
            <p class="text-sm text-muted">New here? <a class="font-semibold text-accent" href="{{ route('register') }}">Create an account</a></p>
        </form>
    </section>
@endsection
