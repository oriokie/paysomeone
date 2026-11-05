@extends('layouts.site')
@section('title', 'Sign in — '.$siteName)
@section('description', 'Sign in with the email and password you already use.')

@section('content')
    <section class="mx-auto max-w-md px-5 py-16">
        <h1 class="font-display text-4xl text-navy">Sign in</h1>
        <p class="mt-3 text-sm leading-6 text-ink/70">Use the email and password on your current account. A password saved in an older format still works.</p>
        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4 rounded-3xl bg-white p-7">
            @csrf
            <div>
                <label class="text-sm font-medium" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium" for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            @if ($errors->any())
                <p class="text-sm text-red-800" role="alert">{{ $errors->first() }}</p>
            @endif
            <button class="w-full rounded-full bg-navy px-5 py-3 text-sm font-semibold text-white" type="submit">Sign in</button>
            <p class="text-sm"><a class="underline" href="{{ route('password.request') }}">Forgot your password?</a></p>
            <p class="text-sm">New here? <a class="underline" href="{{ route('register') }}">Create an account</a></p>
        </form>
    </section>
@endsection
