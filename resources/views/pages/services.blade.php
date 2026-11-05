@extends('layouts.site')
@section('title', 'Services — '.$siteName)
@section('description', 'Articles, editing, proofreading, rewriting, business documents, and resumes for companies and professionals.')

@section('content')
    <section class="mx-auto max-w-6xl px-5 py-16">
        <p class="text-sm font-medium tracking-[0.16em] text-brass uppercase">Services</p>
        <h1 class="mt-4 max-w-3xl font-display text-4xl text-navy sm:text-5xl">Work we will put our name next to.</h1>
        <p class="mt-6 max-w-2xl text-lg leading-8 text-ink/70">Every project starts with who will read it, what it has to do, and when you need it. We do not take academic assignments, essays, coursework, or anything a student would submit as their own.</p>
        <div class="mt-12 space-y-6">
            @foreach ([
                ['Articles and blog posts', 'Priced per word from the article rate card. Tell us the audience, the point of view, and the length.', 'A product launch note, a founder essay, a technical explainer for customers.'],
                ['Editing and proofreading', 'Priced per page. Expert proofreading can be added at 25% of the page subtotal, which is the existing rule.', 'A report that is already drafted and needs a clean, consistent pass.'],
                ['Rewriting', 'Same page rates. Use this when the structure or the sentences need to be rebuilt, not just corrected.', 'A proposal that buries the offer, or a page that sounds like a committee.'],
                ['Business documents', 'Proposals, board updates, one-pagers, and website pages. Page rates, with a specialist-subject option when the field is technical.', 'A tender response or a one-page brief for a client meeting.'],
                ['Resumes and profiles', 'Priced from the resume turnaround list. You supply the facts. We do not invent employers, titles, or dates.', 'A resume, a LinkedIn about section, or a short professional bio.'],
            ] as [$title, $body, $example])
                <article class="grid gap-4 rounded-3xl border border-line bg-white p-7 md:grid-cols-[1fr_1fr]">
                    <div>
                        <h2 class="font-display text-2xl text-navy">{{ $title }}</h2>
                        <p class="mt-3 leading-7 text-ink/75">{{ $body }}</p>
                    </div>
                    <p class="rounded-2xl bg-sand p-5 text-sm leading-6 text-ink/70"><span class="font-medium text-navy">For example. </span>{{ $example }}</p>
                </article>
            @endforeach
        </div>
        <a href="{{ route('order.create') }}" class="mt-10 inline-block rounded-full bg-navy px-5 py-3 text-sm font-semibold text-white">Start a brief</a>
    </section>
@endsection
