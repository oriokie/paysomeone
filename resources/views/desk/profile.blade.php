@extends('layouts.desk')
@section('title', 'Settings — '.$siteName)
@section('heading', 'Settings')

@section('content')
    <div class="max-w-[720px] space-y-8">
        <form class="ui-card space-y-4 p-6 sm:p-8" method="POST" action="{{ route('desk.profile.update') }}">
            @csrf
            <h2 class="font-display text-2xl tracking-tight text-navy">Profile</h2>
            <div>
                <label class="ui-label" for="name">Name</label>
                <input id="name" name="name" value="{{ old('name', $customer->name) }}" required class="ui-input">
            </div>
            <div>
                <label class="ui-label" for="phone">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" class="ui-input">
                <p class="ui-hint">Used if you pay by M-Pesa. Optional.</p>
            </div>
            <p class="text-[13px] text-slate-500">Email stays {{ $customer->email }}. Changing it would split you from the current site.</p>
            <button class="ui-btn ui-btn-primary" type="submit">Save profile</button>
        </form>
        <form class="ui-card space-y-4 p-6 sm:p-8" method="POST" action="{{ route('desk.password.update') }}">
            @csrf
            <h2 class="font-display text-2xl tracking-tight text-navy">Password</h2>
            <div>
                <label class="ui-label" for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" required class="ui-input" autocomplete="current-password">
            </div>
            <div>
                <label class="ui-label" for="password">New password</label>
                <input id="password" name="password" type="password" required minlength="8" class="ui-input" autocomplete="new-password">
                <p class="ui-hint">At least 8 characters. The current site can still check this password.</p>
            </div>
            <div>
                <label class="ui-label" for="password_confirmation">Confirm</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="ui-input" autocomplete="new-password">
            </div>
            @if ($errors->any())
                <p class="text-[13px] text-bad" role="alert">{{ $errors->first() }}</p>
            @endif
            <button class="ui-btn ui-btn-primary" type="submit">Update password</button>
        </form>
    </div>
@endsection
