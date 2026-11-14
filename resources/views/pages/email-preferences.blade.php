@extends('layouts.site')
@section('title', 'Email preferences — '.$siteName)
@section('content')
    <section class="wrap max-w-xl py-16">
        <h1 class="font-display text-4xl text-navy">Email preferences</h1>
        <p class="mt-3 text-slate-500">These choices apply to {{ $email }}. Password resets, receipts, and security mail still send.</p>
        @if (session('status'))
            <p class="mt-4 rounded-2xl bg-good-soft px-4 py-3 text-[13px] text-good" role="status">{{ session('status') }}</p>
        @endif
        <form class="card mt-8 space-y-4 p-6" method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <label class="flex items-center gap-2"><input type="checkbox" name="orders" value="1" @checked($orders)> Order updates</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="messages" value="1" @checked($messages)> Messages</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="payments" value="1" @checked($payments)> Payment notes</label>
            <button class="btn" type="submit">Save preferences</button>
        </form>
    </section>
@endsection
