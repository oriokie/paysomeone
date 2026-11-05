@extends('layouts.site')
@section('title', 'Journal — '.$siteName)
@section('description', 'Notes on briefing a writer, editing a business draft, and the difference between proofreading and rewriting.')

@section('content')
    <section class="mx-auto max-w-6xl px-5 py-16">
        <h1 class="font-display text-4xl text-navy">Journal</h1>
        <p class="mt-4 max-w-2xl leading-7 text-ink/70">Practical notes for people who commission writing. Not study guides.</p>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @forelse ($posts as $post)
                <article class="flex flex-col rounded-3xl border border-line bg-white p-6">
                    <p class="text-xs tracking-wide text-ink/50 uppercase">{{ $post->published_at?->format('j M Y') }}</p>
                    <h2 class="mt-3 font-display text-2xl text-navy"><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>
                    <p class="mt-3 flex-1 text-sm leading-6 text-ink/70">{{ $post->excerpt }}</p>
                    <a class="mt-5 text-sm font-medium text-navy" href="{{ route('blog.show', $post->slug) }}">Read</a>
                </article>
            @empty
                <p class="text-ink/70">No notes published yet.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $posts->links() }}</div>
    </section>
@endsection
