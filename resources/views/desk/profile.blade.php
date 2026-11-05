@extends('layouts.site')
@section('title', 'Profile — '.$siteName)
@section('content')
    @include('desk.nav')
    <section class="mx-auto grid max-w-3xl gap-8 px-5 py-12">
        <h1 class="font-display text-4xl text-navy">Profile</h1>
        @if ($errors->any())
            <p class="text-sm text-red-800" role="alert">{{ $errors->first() }}</p>
        @endif
        <form class="space-y-3 rounded-3xl bg-white p-6" method="POST" action="{{ route('desk.profile.update') }}">
            @csrf
            <label class="block text-sm font-medium" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $customer->name) }}" required class="w-full rounded-xl border border-line px-3 py-2">
            <label class="block text-sm font-medium" for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" class="w-full rounded-xl border border-line px-3 py-2">
            <p class="text-sm text-ink/60">Email stays {{ $customer->email }}. Changing it would split you from the current site.</p>
            <button class="rounded-full bg-navy px-4 py-2 text-sm text-white" type="submit">Save profile</button>
        </form>
        <form class="space-y-3 rounded-3xl bg-white p-6" method="POST" action="{{ route('desk.password.update') }}">
            @csrf
            <h2 class="font-display text-2xl text-navy">Password</h2>
            <label class="block text-sm" for="current_password">Current password</label>
            <input id="current_password" name="current_password" type="password" required class="w-full rounded-xl border border-line px-3 py-2">
            <label class="block text-sm" for="password">New password</label>
            <input id="password" name="password" type="password" required minlength="8" class="w-full rounded-xl border border-line px-3 py-2">
            <label class="block text-sm" for="password_confirmation">Confirm</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-xl border border-line px-3 py-2">
            <button class="rounded-full bg-navy px-4 py-2 text-sm text-white" type="submit">Update password</button>
        </form>
    </section>
@endsection
