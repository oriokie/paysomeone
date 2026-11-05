@extends('layouts.site')

@section('title', $siteName.' — Writing for businesses and professionals')
@section('description', 'Articles, editing, proofreading, rewriting, business documents, and resumes. A clear brief, a real deadline, and a writer matched to the work.')

@section('content')
    <section class="bg-navy text-white">
        <div class="mx-auto grid max-w-6xl items-center gap-14 px-5 py-20 lg:grid-cols-[1.2fr_0.8fr] lg:py-28">
            <div>
                <p class="text-sm font-medium tracking-[0.18em] text-[#d7c4a3] uppercase">Writing studio</p>
                <h1 class="mt-5 font-display text-4xl leading-[1.05] font-semibold sm:text-6xl">Clear writing for work that has to hold up.</h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-white/75">Articles, editing, proofreading, rewriting, business documents, and resumes. You keep the byline. We do the careful part.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('order.create') }}" class="rounded-full bg-white px-5 py-3 text-sm font-semibold text-navy">Price a project</a>
                    <a href="{{ route('services') }}" class="rounded-full border border-white/20 px-5 py-3 text-sm text-white">See the services</a>
                </div>
            </div>
            <aside class="rounded-3xl bg-sand p-7 text-ink shadow-2xl shadow-black/20">
                <p class="text-xs font-medium tracking-[0.16em] text-brass uppercase">A typical article</p>
                <p class="mt-4 font-display text-3xl">800 words, five days</p>
                <p class="mt-3 text-sm leading-6 text-ink/70">The price comes from the same rate card the studio already uses: deadline rate × length, then any extras you actually ask for.</p>
                <a href="{{ route('pricing') }}" class="mt-6 inline-block text-sm font-medium text-navy underline decoration-brass underline-offset-4">How the price is built</a>
            </aside>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 py-20">
        <div class="max-w-2xl">
            <h2 class="font-display text-3xl text-navy sm:text-4xl">What we write</h2>
            <p class="mt-4 text-lg leading-8 text-ink/70">Five kinds of work. No student papers, no homework, no “write this as if I wrote it for class.”</p>
        </div>
        <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Articles', 'Blog posts, explainers, and thought-leadership pieces with a reader in mind.'],
                ['Editing', 'Line editing and proofreading that keep your voice and fix the rest.'],
                ['Rewriting', 'A clearer pass when the draft is right in substance and muddy in the telling.'],
                ['Business documents', 'Proposals, reports, one-pagers, and pages that have to persuade.'],
                ['Resumes', 'Professional profiles that read like a person, not a template.'],
                ['Revisions', 'One included round when the brief was clear. Further changes are quoted first.'],
            ] as [$title, $copy])
                <article class="rounded-3xl border border-line bg-white p-6">
                    <h3 class="font-display text-xl text-navy">{{ $title }}</h3>
                    <p class="mt-3 text-sm leading-6 text-ink/70">{{ $copy }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto grid max-w-6xl gap-12 px-5 py-20 lg:grid-cols-3">
            @foreach (['Tell us the reader and the deadline' => 'A short brief is enough to price the work. The live calculator uses the studio’s existing rate card.', 'A writer is matched' => 'You can ask for a senior specialist or a native English writer. Those tiers use the same percentages as today.', 'Review, then approve' => 'You see the draft, ask for a revision, and keep the files. Payment connections arrive in the next release.'] as $title => $copy)
                <div>
                    <h2 class="font-display text-2xl text-navy">{{ $title }}</h2>
                    <p class="mt-3 leading-7 text-ink/70">{{ $copy }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 py-20">
        <div class="flex items-end justify-between gap-6">
            <h2 class="font-display text-3xl text-navy">Recent client notes</h2>
            <a href="{{ route('reviews') }}" class="text-sm font-medium text-navy underline decoration-brass underline-offset-4">All reviews</a>
        </div>
        <div class="mt-8 grid gap-5 md:grid-cols-3">
            @forelse ($reviews as $review)
                <figure class="rounded-3xl border border-line bg-white p-6">
                    <p class="text-brass" aria-label="{{ (int) $review->service_rating }} out of 5">{{ str_repeat('★', (int) $review->service_rating) }}</p>
                    <blockquote class="mt-4 text-sm leading-6">{{ \Illuminate\Support\Str::limit($review->comment, 280) }}</blockquote>
                    <figcaption class="mt-4 text-xs tracking-wide text-ink/50 uppercase">Verified client</figcaption>
                </figure>
            @empty
                <p class="text-ink/70 md:col-span-3">Reviews from completed work will show here. None are published in this database yet.</p>
            @endforelse
        </div>
    </section>
@endsection
