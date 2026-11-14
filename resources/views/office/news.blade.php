@extends('layouts.office')
@section('heading', 'News')
@section('content')
    @if (\App\Services\Office\Access::allows($staff, 'news.manage'))
        <form class="ui-card mb-6 space-y-3 p-6" method="POST" action="{{ route('office.news.store') }}">
            @csrf
            <input name="title" required placeholder="Title" class="ui-input">
            <textarea name="content" required rows="4" class="ui-input h-auto py-3" placeholder="Announcement"></textarea>
            <select name="recipient" class="ui-input"><option value="all">Everyone</option><option value="writers">Writers</option><option value="editors">Editors</option></select>
            <button class="ui-btn ui-btn-primary" type="submit">Publish</button>
        </form>
    @endif
    <div class="space-y-3">
        @foreach ($items as $item)
            <article class="ui-card p-5"><h2 class="font-display text-xl tracking-tight">{{ $item->title }}</h2><p class="mt-2 whitespace-pre-wrap text-[15px] text-slate-600">{{ $item->content }}</p></article>
        @endforeach
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection
