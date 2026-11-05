@extends('layouts.checkout')
@section('title', 'Reset office password — '.$siteName)
@section('content')
    <section class="mx-auto max-w-[420px] px-5 py-16">
        <h1 class="font-display text-[32px] tracking-tight text-navy">Reset your office password</h1>
        <p class="mt-3 text-[15px] text-slate-500">We email a link if this address is on the staff list.</p>
        @if (session('status'))
            <p class="mt-4 text-[13px] text-slate-600" role="status">{{ session('status') }}</p>
        @endif
        <form class="ui-card mt-8 space-y-4 p-6" method="POST" action="{{ route('office.forgot.send') }}">
            @csrf
            <label class="ui-label" for="email">Email</label>
            <input id="email" name="email" type="email" required class="ui-input" value="{{ old('email') }}">
            @if ($errors->any())<p class="text-[13px] text-bad" role="alert">{{ $errors->first() }}</p>@endif
            <button class="ui-btn ui-btn-primary w-full" type="submit">Send the link</button>
        </form>
    </section>
@endsection
