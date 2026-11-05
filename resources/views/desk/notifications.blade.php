@extends('layouts.desk')
@section('title', 'Notifications — '.$siteName)
@section('heading', 'Notifications')

@section('content')
    <ul class="ui-card divide-y divide-slate-100">
        @forelse ($notes as $note)
            <li class="px-5 py-4">
                <p class="text-[15px]">{{ $note->message }}</p>
                <p class="mt-1 text-[13px] text-slate-500">{{ $note->date_add }} · {{ $note->is_seen ? 'Seen' : 'New' }}</p>
            </li>
        @empty
            <li class="px-5 py-16 text-center">
                <x-glyph name="bell" class="mx-auto size-8 text-slate-300" />
                <p class="mt-3 font-display text-xl text-navy">You’re caught up</p>
                <p class="mt-2 text-[13px] text-slate-500">Payment and order notes will appear here.</p>
            </li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $notes->links() }}</div>
@endsection
