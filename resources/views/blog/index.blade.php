@extends('layouts.site')
@section('title', 'Journal — '.$siteName)
@section('description', 'Notes on briefing a writer, editing a business draft, and the difference between proofreading and rewriting.')

@section('content')
    <section class="wrap py-16 lg:py-20">
        <p class="eyebrow">Journal</p>
        <h1 class="mt-3 font-display text-5xl text-navy">Notes for people who commission writing.</h1>
        <p class="lede mt-4">Practical, not study guides.</p>
        <div class="mt-12 grid gap-4 md:grid-cols-3">
            @forelse ($posts as $post)
                <article class="card card-hover flex flex-col p-6">
                    <p class="text-xs font-medium tracking-wide text-muted uppercase">{{ $post->published_at?->format('j M Y') }}</p>
                    <h2 class="mt-3 font-display text-2xl text-navy"><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>
                    <p class="mt-3 flex-1 text-sm leading-6 text-muted">{{ $post->excerpt }}</p>
                    <a class="mt-5 text-sm font-semibold text-accent" href="{{ route('blog.show', $post->slug) }}">Read</a>
                </article>
            @empty
                <p class="text-muted">No notes published yet.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $posts->links() }}</div>
    </section>
@endsection
