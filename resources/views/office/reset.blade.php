@extends('layouts.checkout')
@section('title', 'Choose a password — '.$siteName)
@section('content')
    <section class="mx-auto max-w-[420px] px-5 py-16">
        <h1 class="font-display text-[32px] tracking-tight text-navy">Choose a new password</h1>
        <form class="ui-card mt-8 space-y-4 p-6" method="POST" action="{{ route('office.reset.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="ui-label" for="email">Email</label>
            <input id="email" name="email" type="email" required class="ui-input" value="{{ old('email', $email) }}">
            <label class="ui-label" for="password">New password</label>
            <input id="password" name="password" type="password" required minlength="8" class="ui-input">
            <label class="ui-label" for="password_confirmation">Confirm</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="ui-input">
            @if ($errors->any())<p class="text-[13px] text-bad" role="alert">{{ $errors->first() }}</p>@endif
            <button class="ui-btn ui-btn-primary w-full" type="submit">Update password</button>
        </form>
    </section>
@endsection
