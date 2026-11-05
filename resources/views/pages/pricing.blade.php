@extends('layouts.site')
@section('title', 'Pricing — '.$siteName)
@section('description', 'Prices come from the studio rate card: per word for articles, per page for documents, plus the extras you choose.')

@section('content')
    <section class="wrap py-16 lg:py-20">
        <p class="eyebrow">Pricing</p>
        <h1 class="mt-3 max-w-3xl font-display text-5xl text-navy">The rate card, in plain language.</h1>
        <div class="mt-10 grid gap-4 lg:grid-cols-2">
            <article class="card p-7">
                <h2 class="font-display text-2xl text-navy">Articles</h2>
                <p class="mt-3 leading-7 text-muted">Word count times the per-word rate for the turnaround you pick. A senior specialist adds 25%. A native English writer adds 30%. A percent discount applies to the word subtotal.</p>
            </article>
            <article class="card p-7">
                <h2 class="font-display text-2xl text-navy">Pages</h2>
                <p class="mt-3 leading-7 text-muted">Resumes, business documents, editing, and rewriting use the page rate. Single spacing is twice the double-spaced rate. Slides and charts, when a project needs them later, are half the page rate.</p>
            </article>
        </div>
        <div class="card mt-6 overflow-hidden">
            <table class="data-table">
                <caption class="sr-only">Turnaround rates currently stored for this site</caption>
                <thead>
                    <tr>
                        <th scope="col">Service</th>
                        <th scope="col">Turnaround</th>
                        <th scope="col">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @php $any = false; @endphp
                    @foreach ($services as $key => $label)
                        @foreach ($rates[$key] ?? [] as $rate)
                            @php $any = true; @endphp
                            <tr>
                                <th class="border-t border-line px-4 py-3.5 text-left font-medium text-navy" scope="row">{{ $label }}</th>
                                <td>{{ $rate['name'] }}</td>
                                <td>${{ $rate['unit'] === 'word' ? number_format($rate['rate'], 4) : number_format($rate['rate'], 2) }} / {{ $rate['unit'] }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    @unless ($any)
                        <tr><td class="px-4 py-8 text-muted" colspan="3">No rates are loaded yet. Add turnaround rows to the existing price tables, or run the local demo seeder on a copy of the database.</td></tr>
                    @endunless
                </tbody>
            </table>
        </div>
        <h2 class="mt-16 font-display text-3xl text-navy">Extras, when you ask for them</h2>
        <ul class="mt-6 grid gap-3 md:grid-cols-2">
            @foreach ([
                'Progressive delivery — 10%, only from $200 and 5 days. Included automatically from $600 and 7 days.',
                'Copy of sources — 10% of the page subtotal, and never less than $14.95.',
                'Specialist or technical subject — 20%.',
                'Expert proofreading — 25%.',
                'Draft or outline — 25%.',
                'A specific writer — 10%.',
                'Earlier writing samples — $5.',
                'VIP support or a plagiarism report — $9.99 each.',
            ] as $item)
                <li class="card px-5 py-4 text-sm leading-6">{{ $item }}</li>
            @endforeach
        </ul>
        <a href="{{ route('order.create') }}" class="btn btn-primary mt-10">Open the calculator</a>
    </section>
@endsection
