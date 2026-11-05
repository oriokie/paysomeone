@extends('layouts.site')
@section('title', 'Reset your password — '.$siteName)

@section('content')
    <section class="mx-auto max-w-md px-5 py-16">
        <h1 class="font-display text-4xl text-navy">Reset your password</h1>
        <p class="mt-3 text-sm leading-6 text-ink/70">While this preview is read-only, change the password on the current website. Both sites will then accept it.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-4 rounded-3xl bg-white p-7">
            @csrf
            <div>
                <label class="text-sm font-medium" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            @if ($errors->any())
                <p class="text-sm text-red-800" role="alert">{{ $errors->first() }}</p>
            @endif
            <button class="w-full rounded-full bg-navy px-5 py-3 text-sm font-semibold text-white" type="submit">Send a reset link</button>
        </form>
    </section>
@endsection
