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
        <form class="ui-card space-y-4 p-6 sm:p-8" method="POST" action="{{ route('desk.preferences') }}">
            @csrf
            <h2 class="font-display text-2xl tracking-tight text-navy">Email</h2>
            <p class="text-[13px] text-slate-500">Order updates, messages, and payment notes can be turned off. Password resets, receipts, and security mail still send.</p>
            <label class="flex items-center gap-2 text-[15px]"><input type="checkbox" name="orders" value="1" @checked(old('orders', $pref->orders ?? true))> Order updates</label>
            <label class="flex items-center gap-2 text-[15px]"><input type="checkbox" name="messages" value="1" @checked(old('messages', $pref->messages ?? true))> Messages</label>
            <label class="flex items-center gap-2 text-[15px]"><input type="checkbox" name="payments" value="1" @checked(old('payments', $pref->payments ?? true))> Payment notes</label>
            <button class="ui-btn ui-btn-primary" type="submit">Save email preferences</button>
        </form>
        <section class="ui-card space-y-4 p-6 sm:p-8">
            <h2 class="font-display text-2xl tracking-tight text-navy">Your data</h2>
            <p class="text-[13px] text-slate-500">Download the profile, orders, and payment attempts stored for this account. It does not include anyone else.</p>
            <a class="ui-btn ui-btn-quiet" href="{{ route('desk.export') }}">Download JSON</a>
            <form class="space-y-3 border-t border-slate-100 pt-4" method="POST" action="{{ route('desk.deletion') }}">
                @csrf
                <p class="text-[13px] text-slate-500">Ask to remove the name, email, and phone on this account. Orders stay so the studio can keep its records. This signs you out when writes are allowed.</p>
                <button class="ui-btn ui-btn-quiet" type="submit">Request account removal</button>
            </form>
        </section>
    </div>
@endsection
