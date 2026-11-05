@extends('layouts.site')
@section('title', 'Choose a new password — '.$siteName)

@section('content')
    <section class="mx-auto max-w-md px-5 py-16">
        <h1 class="font-display text-4xl text-navy">Choose a new password</h1>
        <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-4 rounded-3xl bg-white p-7">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="text-sm font-medium" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium" for="password">New password</label>
                <input id="password" name="password" type="password" required minlength="8" class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" class="mt-1 w-full rounded-xl border border-line px-3 py-2">
            </div>
            @if ($errors->any())
                <p class="text-sm text-red-800" role="alert">{{ $errors->first() }}</p>
            @endif
            <button class="w-full rounded-full bg-navy px-5 py-3 text-sm font-semibold text-white" type="submit">Update password</button>
        </form>
    </section>
@endsection
