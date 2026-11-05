@extends('layouts.site')
@section('title', $post->title.' — '.$siteName)
@section('description', $post->meta_description ?: $post->excerpt)
@section('canonical', route('blog.show', $post->slug))

@section('content')
    <article class="mx-auto max-w-3xl px-5 py-16">
        <p class="text-xs tracking-wide text-ink/50 uppercase">{{ $post->published_at?->format('j M Y') }} · {{ $post->author_name }}</p>
        <h1 class="mt-4 font-display text-4xl text-navy sm:text-5xl">{{ $post->title }}</h1>
        <div class="prose mt-8 max-w-none leading-8 text-ink/85">
            {!! \Illuminate\Support\Str::markdown($post->body) !!}
        </div>
    </article>
@endsection
