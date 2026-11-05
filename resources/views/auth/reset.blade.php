@extends('layouts.site')
@section('title', 'Choose a new password — '.$siteName)
@section('content')
    <section class="wrap flex max-w-md flex-col py-16 lg:py-24">
        <h1 class="font-display text-4xl text-navy">Choose a new password</h1>
        <form method="POST" action="{{ route('password.update') }}" class="card mt-8 space-y-4 p-7">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="field"></div>
            <div><label class="label" for="password">New password</label><input id="password" name="password" type="password" required minlength="8" class="field"></div>
            <div><label class="label" for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" class="field"></div>
            @if ($errors->any())<p class="text-sm text-bad" role="alert">{{ $errors->first() }}</p>@endif
            <button class="btn btn-primary w-full" type="submit">Update password</button>
        </form>
    </section>
@endsection
