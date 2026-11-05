@extends('layouts.site')
@section('title', 'Reset your password — '.$siteName)
@section('content')
    <section class="wrap flex max-w-md flex-col py-16 lg:py-24">
        <h1 class="font-display text-4xl text-navy">Reset your password</h1>
        <p class="mt-3 text-sm leading-6 text-muted">While this preview is read-only, change the password on the current website. Both sites will then accept it.</p>
        <form method="POST" action="{{ route('password.email') }}" class="card mt-8 space-y-4 p-7">
            @csrf
            <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required class="field"></div>
            @if ($errors->any())<p class="text-sm text-bad" role="alert">{{ $errors->first() }}</p>@endif
            <button class="btn btn-primary w-full" type="submit">Send a reset link</button>
        </form>
    </section>
@endsection
