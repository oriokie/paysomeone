@extends('layouts.site')
@section('title', 'Reviews — '.$siteName)
@section('description', 'Notes from clients on completed work. Names and order details are not shown.')

@section('content')
    <section class="mx-auto max-w-6xl px-5 py-16">
        <h1 class="font-display text-4xl text-navy sm:text-5xl">Reviews</h1>
        <p class="mt-4 max-w-2xl leading-7 text-ink/70">These are service notes already stored with completed work. The client’s name, email, and the title of the project stay private.</p>
        <div class="mt-10 grid gap-5 md:grid-cols-2">
            @forelse ($reviews as $review)
                <figure class="rounded-3xl border border-line bg-white p-6">
                    <p class="text-brass" aria-label="Service {{ (int) $review->service_rating }} out of 5, work {{ (int) $review->paper_rating }} out of 5">
                        Service {{ (int) $review->service_rating }}/5
                        @if ($review->paper_rating)
                            <span class="text-ink/40"> · </span> Work {{ (int) $review->paper_rating }}/5
                        @endif
                    </p>
                    <blockquote class="mt-4 leading-7">{{ $review->comment }}</blockquote>
                    <figcaption class="mt-4 text-xs tracking-wide text-ink/50 uppercase">
                        Verified client
                        @if ($review->date_add)
                            · {{ $review->date_add->timezone(config('app.timezone'))->format('j M Y') }}
                        @endif
                        @if ($review->paper_on_time)
                            · On time
                        @endif
                    </figcaption>
                </figure>
            @empty
                <p class="text-ink/70 md:col-span-2">No client reviews are stored yet.</p>
            @endforelse
        </div>
    </section>
@endsection
