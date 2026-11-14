@extends('layouts.site')
@section('title', $post->title.' — '.$siteName)
@section('description', $post->meta_description ?: $post->excerpt)
@section('canonical', route('blog.show', $post->slug))

@section('content')
    <article class="wrap max-w-3xl py-16 lg:py-20">
        <p class="text-xs font-medium tracking-wide text-muted uppercase">{{ $post->published_at?->format('j M Y') }} · {{ $post->author_name }}</p>
        <h1 class="mt-4 font-display text-5xl leading-[1.08] text-navy">{{ $post->title }}</h1>
        @if ($post->excerpt)
            <p class="mt-6 text-xl leading-8 text-muted">{{ $post->excerpt }}</p>
        @endif
        <div class="prose mt-10 max-w-[40rem]">
            {!! \Illuminate\Support\Str::markdown($post->body) !!}
        </div>
    </article>
@endsection
