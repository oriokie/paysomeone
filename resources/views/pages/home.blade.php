@extends('layouts.site')

@section('title', $siteName.' — Writing for businesses and professionals')
@section('description', 'Articles, editing, proofreading, rewriting, business documents, and resumes. A clear brief, a real deadline, and a writer matched to the work.')

@section('content')
    <section class="hero text-white">
        <div class="wrap grid items-center gap-16 py-20 lg:grid-cols-[1.15fr_0.85fr] lg:py-28">
            <div>
                <p class="text-xs font-semibold tracking-[0.18em] text-accent-bright uppercase">Writing studio</p>
                <h1 class="mt-5 max-w-xl font-display text-5xl leading-[1.02] font-semibold text-white sm:text-6xl">Clear writing for work that has to hold up.</h1>
                <p class="mt-6 max-w-lg text-lg leading-8 text-white/75">Articles, editing, proofreading, rewriting, business documents, and resumes. You keep the byline. We do the careful part.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('order.create') }}" class="btn btn-invert">Price a project</a>
                    <a href="{{ route('services') }}" class="btn btn-on-dark">See the services</a>
                </div>
            </div>
            <aside class="card p-7 text-ink">
                <p class="eyebrow">From the rate card</p>
                <p class="mt-4 font-display text-3xl text-navy">800 words, five days</p>
                <p class="mt-3 text-sm leading-6 text-muted">The price is the deadline rate times the length, then only the extras you ask for. The same card the studio already uses.</p>
                <a href="{{ route('pricing') }}" class="mt-6 inline-flex text-sm font-semibold text-accent">How the price is built</a>
            </aside>
        </div>
    </section>

    <section class="wrap py-section">
        <div class="max-w-2xl">
            <p class="eyebrow">Services</p>
            <h2 class="mt-3 font-display text-4xl text-navy">What we write</h2>
            <p class="lede mt-4">Five kinds of work. No student papers, no homework, and nothing a student would submit as their own.</p>
        </div>
        <div class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['pen', 'Articles', 'Blog posts, explainers, and thought-leadership pieces with a reader in mind.'],
                ['check', 'Editing', 'Line editing and proofreading that keep your voice and fix the rest.'],
                ['refresh', 'Rewriting', 'A clearer pass when the draft is right in substance and muddy in the telling.'],
                ['doc', 'Business documents', 'Proposals, reports, one-pagers, and pages that have to persuade.'],
                ['user', 'Resumes', 'Professional profiles that read like a person, not a template.'],
                ['loop', 'Revisions', 'One included round when the brief was clear. Further changes are quoted first.'],
            ] as [$icon, $title, $copy])
                <article class="card card-hover p-6">
                    <span class="grid size-10 place-items-center rounded-xl bg-paper text-navy" aria-hidden="true">
                        @include('icons.'.$icon)
                    </span>
                    <h3 class="mt-5 font-display text-xl text-navy">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-muted">{{ $copy }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-y border-line bg-paper-raised">
        <div class="wrap py-section">
            <p class="eyebrow">How it works</p>
            <h2 class="mt-3 font-display text-4xl text-navy">Three steps, then a draft.</h2>
            <ol class="mt-12 grid gap-8 lg:grid-cols-3">
                @foreach ([
                    ['01', 'Tell us the reader and the deadline', 'A short brief is enough to price the work. The calculator uses the studio’s existing rate card.'],
                    ['02', 'A writer is matched', 'Best available, a senior specialist, or a native English writer. The percentages are the ones already in use.'],
                    ['03', 'Review, then approve', 'You see the draft, ask for a revision, and keep the files. Payment stays off on this preview until safe mode is turned off.'],
                ] as [$n, $title, $copy])
                    <li>
                        <p class="font-display text-3xl text-accent-bright">{{ $n }}</p>
                        <h3 class="mt-3 font-display text-2xl text-navy">{{ $title }}</h3>
                        <p class="mt-3 leading-7 text-muted">{{ $copy }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="wrap py-section">
        <div class="flex items-end justify-between gap-6">
            <div>
                <p class="eyebrow">From completed work</p>
                <h2 class="mt-3 font-display text-4xl text-navy">Client notes</h2>
            </div>
            <a href="{{ route('reviews') }}" class="text-sm font-semibold text-accent">All reviews</a>
        </div>
        <div class="mt-10 grid gap-4 md:grid-cols-3">
            @forelse ($reviews as $review)
                <figure class="card p-6">
                    <p class="text-sm font-semibold text-accent" aria-label="{{ (int) $review->service_rating }} out of 5">{{ (int) $review->service_rating }}/5 service</p>
                    <blockquote class="mt-4 text-sm leading-7">{{ \Illuminate\Support\Str::limit($review->comment, 280) }}</blockquote>
                    <figcaption class="mt-5 text-xs font-medium tracking-wide text-muted uppercase">Verified client</figcaption>
                </figure>
            @empty
                <p class="text-muted md:col-span-3">Reviews from completed work will show here. None are stored in this database yet.</p>
            @endforelse
        </div>
    </section>
@endsection
