@extends('layouts.site')
@section('title', 'Reviews — '.$siteName)
@section('description', 'Notes from clients on completed work. Names and order details are not shown.')

@section('content')
    <section class="wrap py-16 lg:py-20">
        <p class="eyebrow">Reviews</p>
        <h1 class="mt-3 font-display text-5xl text-navy">Notes from completed work</h1>
        <p class="lede mt-4">These are service notes already stored with finished orders. The client’s name, email, and the title of the project stay private.</p>
        <div class="mt-12 grid gap-4 md:grid-cols-2">
            @forelse ($reviews as $review)
                <figure class="card p-6">
                    <p class="text-sm font-semibold text-accent">Service {{ (int) $review->service_rating }}/5 @if ($review->paper_rating)<span class="font-normal text-muted">· Work {{ (int) $review->paper_rating }}/5</span>@endif</p>
                    <blockquote class="mt-4 leading-7">{{ $review->comment }}</blockquote>
                    <figcaption class="mt-4 text-xs font-medium tracking-wide text-muted uppercase">
                        Verified client
                        @if ($review->date_add) · {{ $review->date_add->timezone(config('app.timezone'))->format('j M Y') }} @endif
                        @if ($review->paper_on_time) · On time @endif
                    </figcaption>
                </figure>
            @empty
                <p class="text-muted md:col-span-2">No client reviews are stored yet.</p>
            @endforelse
        </div>
    </section>
@endsection
