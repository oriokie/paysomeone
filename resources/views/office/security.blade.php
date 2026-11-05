@extends('layouts.checkout')
@section('title', 'Two-factor — '.$siteName)
@section('content')
    <section class="mx-auto max-w-[560px] px-5 py-16">
        <p class="text-[13px] font-medium text-slate-500">Office</p>
        @if ($mode === 'setup')
            <h1 class="mt-2 font-display text-[32px] tracking-tight text-navy">Set up an authenticator</h1>
            <p class="mt-3 text-[15px] text-slate-500">Add this key to Google Authenticator, 1Password, or Authy. The office stays closed until a code matches.</p>
            <div class="ui-card mt-6 p-6">
                <p class="text-[13px] text-slate-500">Secret</p>
                <p class="mt-1 font-mono text-lg tracking-widest">{{ $secret }}</p>
                <p class="mt-4 break-all text-[13px] text-slate-500">{{ $uri }}</p>
            </div>
            @if ($codes)
                <div class="ui-card mt-4 p-6">
                    <p class="font-medium">Recovery codes</p>
                    <p class="mt-1 text-[13px] text-slate-500">Shown once. Each code works a single time if you lose the phone.</p>
                    <ul class="mt-3 grid grid-cols-2 gap-2 font-mono text-[13px]">
                        @foreach ($codes as $code)<li>{{ $code }}</li>@endforeach
                    </ul>
                </div>
            @endif
            <form class="mt-6 space-y-4" method="POST" action="{{ route('office.setup.confirm') }}">
                @csrf
                <label class="ui-label" for="code">6-digit code</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required class="ui-input tracking-[0.3em]" placeholder="123456">
                @if ($errors->any())<p class="text-[13px] text-bad">{{ $errors->first() }}</p>@endif
                <button class="ui-btn ui-btn-primary" type="submit">Turn on two-factor</button>
            </form>
        @else
            <h1 class="mt-2 font-display text-[32px] tracking-tight text-navy">Enter your code</h1>
            <p class="mt-3 text-[15px] text-slate-500">Use the authenticator app, or one recovery code.</p>
            <form class="ui-card mt-6 space-y-4 p-6" method="POST" action="{{ route('office.challenge.verify') }}">
                @csrf
                <label class="ui-label" for="code">Code</label>
                <input id="code" name="code" required class="ui-input tracking-[0.3em]" autocomplete="one-time-code">
                @if ($errors->any())<p class="text-[13px] text-bad">{{ $errors->first() }}</p>@endif
                <button class="ui-btn ui-btn-primary" type="submit">Enter the office</button>
            </form>
        @endif
    </section>
@endsection
