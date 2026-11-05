@extends('layouts.desk')
@section('title', 'Profile — '.$siteName)
@section('content')
    <h1 class="font-display text-4xl text-navy">Profile</h1>
    <form class="card mt-8 max-w-lg space-y-4 p-6" method="POST" action="{{ route('desk.profile.update') }}">
        @csrf
        <div><label class="label" for="name">Name</label><input id="name" name="name" value="{{ old('name', $customer->name) }}" required class="field"></div>
        <div><label class="label" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" class="field"></div>
        <p class="text-sm text-muted">Email stays {{ $customer->email }}. Changing it would split you from the current site.</p>
        <button class="btn btn-primary" type="submit">Save profile</button>
    </form>
    <form class="card mt-6 max-w-lg space-y-4 p-6" method="POST" action="{{ route('desk.password.update') }}">
        @csrf
        <h2 class="font-display text-2xl text-navy">Password</h2>
        <div><label class="label" for="current_password">Current password</label><input id="current_password" name="current_password" type="password" required class="field"></div>
        <div><label class="label" for="password">New password</label><input id="password" name="password" type="password" required minlength="8" class="field"></div>
        <div><label class="label" for="password_confirmation">Confirm</label><input id="password_confirmation" name="password_confirmation" type="password" required class="field"></div>
        <button class="btn btn-primary" type="submit">Update password</button>
    </form>
@endsection
