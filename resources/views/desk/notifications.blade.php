@extends('layouts.desk')
@section('title', 'Alerts — '.$siteName)
@section('content')
    <h1 class="font-display text-4xl text-navy">Alerts</h1>
    <ul class="card mt-6 divide-y divide-line">
        @forelse ($notes as $note)
            <li class="px-5 py-4">
                <p class="text-sm">{{ $note->message }}</p>
                <p class="mt-1 text-xs text-muted">{{ $note->date_add }} · {{ $note->is_seen ? 'Seen' : 'New' }}</p>
            </li>
        @empty
            <li class="px-5 py-10 text-center">
                <p class="font-display text-xl text-navy">You’re caught up</p>
                <p class="mt-2 text-sm text-muted">Payment and order notes will appear here.</p>
            </li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $notes->links() }}</div>
@endsection
