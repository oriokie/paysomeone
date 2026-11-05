@extends('layouts.site')
@section('title', 'Pricing — '.$siteName)
@section('description', 'Prices come from the studio rate card: per word for articles, per page for documents, plus the extras you choose.')

@section('content')
    <section class="mx-auto max-w-6xl px-5 py-16">
        <p class="text-sm font-medium tracking-[0.16em] text-brass uppercase">Pricing</p>
        <h1 class="mt-4 max-w-3xl font-display text-4xl text-navy sm:text-5xl">The rate card, in plain language.</h1>
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl bg-white p-7">
                <h2 class="font-display text-2xl text-navy">Articles</h2>
                <p class="mt-3 leading-7 text-ink/75">Word count times the per-word rate for the turnaround you pick. A senior specialist adds 25%. A native English writer adds 30%. A percent discount applies to the word subtotal.</p>
            </div>
            <div class="rounded-3xl bg-white p-7">
                <h2 class="font-display text-2xl text-navy">Pages</h2>
                <p class="mt-3 leading-7 text-ink/75">Resumes, business documents, editing, and rewriting use the page rate. Single spacing is twice the double-spaced rate. Slides and charts, when a project needs them later, are half the page rate.</p>
            </div>
        </div>

        <div class="mt-8 overflow-hidden rounded-3xl border border-line bg-white">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Turnaround rates currently stored for this site</caption>
                <thead class="bg-sand text-ink/70">
                    <tr>
                        <th class="px-5 py-3 font-medium" scope="col">Service</th>
                        <th class="px-5 py-3 font-medium" scope="col">Turnaround</th>
                        <th class="px-5 py-3 font-medium" scope="col">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @php $any = false; @endphp
                    @foreach ($services as $key => $label)
                        @foreach ($rates[$key] ?? [] as $rate)
                            @php $any = true; @endphp
                            <tr class="border-t border-line">
                                <th class="px-5 py-3 font-medium text-navy" scope="row">{{ $label }}</th>
                                <td class="px-5 py-3">{{ $rate['name'] }}</td>
                                <td class="px-5 py-3">${{ $rate['unit'] === 'word' ? number_format($rate['rate'], 4) : number_format($rate['rate'], 2) }} / {{ $rate['unit'] }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    @unless ($any)
                        <tr><td class="px-5 py-6 text-ink/70" colspan="3">No rates are loaded yet. Add turnaround rows to the existing price tables, or run the local demo seeder on a copy of the database.</td></tr>
                    @endunless
                </tbody>
            </table>
        </div>

        <h2 class="mt-14 font-display text-3xl text-navy">Extras, when you ask for them</h2>
        <ul class="mt-6 grid gap-4 md:grid-cols-2">
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
                <li class="rounded-2xl border border-line bg-white px-5 py-4 text-sm leading-6">{{ $item }}</li>
            @endforeach
        </ul>
        <a href="{{ route('order.create') }}" class="mt-10 inline-block rounded-full bg-navy px-5 py-3 text-sm font-semibold text-white">Open the calculator</a>
    </section>
@endsection
