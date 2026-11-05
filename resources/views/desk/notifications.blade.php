@extends('layouts.site')
@section('title', 'Alerts — '.$siteName)
@section('content')
    @include('desk.nav')
    <section class="mx-auto max-w-3xl px-5 py-12">
        <h1 class="font-display text-4xl text-navy">Alerts</h1>
        <ul class="mt-6 divide-y divide-line rounded-3xl border border-line bg-white">
            @forelse ($notes as $note)
                <li class="px-5 py-4">
                    <p class="text-sm">{{ $note->message }}</p>
                    <p class="mt-1 text-xs text-ink/50">{{ $note->date_add }} · {{ $note->is_seen ? 'Seen' : 'New' }}</p>
                </li>
            @empty
                <li class="px-5 py-6 text-sm text-ink/60">No alerts yet.</li>
            @endforelse
        </ul>
        <div class="mt-6">{{ $notes->links() }}</div>
    </section>
@endsection
