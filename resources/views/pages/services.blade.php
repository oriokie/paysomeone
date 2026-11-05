@extends('layouts.site')
@section('title', 'Services — '.$siteName)
@section('description', 'Articles, editing, proofreading, rewriting, business documents, and resumes for companies and professionals.')

@section('content')
    <section class="wrap py-16 lg:py-20">
        <p class="eyebrow">Services</p>
        <h1 class="mt-3 max-w-3xl font-display text-5xl text-navy">Work we will put our name next to.</h1>
        <p class="lede mt-5">Every project starts with who will read it, what it has to do, and when you need it. We do not take academic assignments, essays, coursework, or anything a student would submit as their own.</p>
        <div class="mt-14 space-y-4">
            @foreach ([
                ['pen', 'Articles and blog posts', 'Priced per word from the article rate card. Tell us the audience, the point of view, and the length.', 'A product launch note, a founder essay, a technical explainer for customers.'],
                ['check', 'Editing and proofreading', 'Priced per page. Expert proofreading can be added at 25% of the page subtotal, which is the existing rule.', 'A report that is already drafted and needs a clean, consistent pass.'],
                ['refresh', 'Rewriting', 'Same page rates. Use this when the structure or the sentences need to be rebuilt, not just corrected.', 'A proposal that buries the offer, or a page that sounds like a committee.'],
                ['doc', 'Business documents', 'Proposals, board updates, one-pagers, and website pages. Page rates, with a specialist-subject option when the field is technical.', 'A tender response or a one-page brief for a client meeting.'],
                ['user', 'Resumes and profiles', 'Priced from the resume turnaround list. You supply the facts. We do not invent employers, titles, or dates.', 'A resume, a LinkedIn about section, or a short professional bio.'],
            ] as [$icon, $title, $body, $example])
                <article class="card grid gap-6 p-7 md:grid-cols-2">
                    <div>
                        <span class="grid size-10 place-items-center rounded-xl bg-paper text-navy" aria-hidden="true">@include('icons.'.$icon)</span>
                        <h2 class="mt-4 font-display text-2xl text-navy">{{ $title }}</h2>
                        <p class="mt-3 leading-7 text-muted">{{ $body }}</p>
                    </div>
                    <p class="rounded-2xl bg-paper p-5 text-sm leading-7 text-muted"><span class="font-semibold text-navy">For example. </span>{{ $example }}</p>
                </article>
            @endforeach
        </div>
        <a href="{{ route('order.create') }}" class="btn btn-primary mt-10">Start a brief</a>
    </section>
@endsection
