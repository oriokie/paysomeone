@extends('layouts.site')
@section('title', 'Start a brief — '.$siteName)
@section('description', 'Price an article, a resume, an edit, or a rewrite from the studio rate card before you send the brief.')

@section('content')
    <section class="mx-auto grid max-w-6xl gap-10 px-5 py-16 lg:grid-cols-[1.2fr_0.8fr]" x-data="orderForm(@js($options))">
        <div>
            <h1 class="font-display text-4xl text-navy">Start a brief</h1>
            <p class="mt-4 max-w-xl leading-7 text-ink/70">The total updates from the rate card. This preview does not take payment.</p>

            <form method="POST" action="{{ route('order.store') }}" class="mt-8 space-y-6" @change="schedule()">
                @csrf
                <fieldset>
                    <legend class="text-sm font-medium">Service</legend>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <template x-for="(label, key) in options.services" :key="key">
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line bg-white px-4 py-3">
                                <input type="radio" name="service" :value="key" x-model="service" class="mt-1">
                                <span x-text="label"></span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <div>
                    <label class="text-sm font-medium" for="deadline_id">Turnaround</label>
                    <select id="deadline_id" name="deadline_id" x-model="deadlineId" @change="schedule()" class="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2">
                        <template x-for="deadline in deadlines" :key="deadline.id">
                            <option :value="deadline.id" x-text="deadline.name + ' — $' + (deadline.unit === 'word' ? Number(deadline.rate).toFixed(4) : Number(deadline.rate).toFixed(2)) + ' / ' + deadline.unit"></option>
                        </template>
                    </select>
                </div>

                <div x-show="usesWords">
                    <label class="text-sm font-medium" for="words">Words</label>
                    <input id="words" name="words" type="number" min="100" max="20000" x-model.number="words" @input="schedule()" class="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2">
                </div>
                <div x-show="!usesWords" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium" for="pages">Pages</label>
                        <input id="pages" name="pages" type="number" min="1" max="200" x-model.number="pages" @input="schedule()" class="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2">
                    </div>
                    <div>
                        <label class="text-sm font-medium" for="spacing">Spacing</label>
                        <select id="spacing" name="spacing" x-model="spacing" @change="schedule()" class="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2">
                            <option value="double">Double (standard page rate)</option>
                            <option value="single">Single (twice the page rate)</option>
                        </select>
                    </div>
                </div>

                <fieldset>
                    <legend class="text-sm font-medium">Writer</legend>
                    <div class="mt-3 space-y-2">
                        <template x-for="(tier, id) in options.tiers" :key="id">
                            <label class="flex gap-3 rounded-2xl border border-line bg-white px-4 py-3">
                                <input type="radio" name="writer_category_id" :value="id" x-model.number="writerCategoryId">
                                <span>
                                    <span class="block font-medium" x-text="tier.label"></span>
                                    <span class="block text-sm text-ink/60" x-text="tier.detail"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <fieldset x-show="!usesWords">
                    <legend class="text-sm font-medium">Extras</legend>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ([
                            'proofreading' => 'Expert proofreading (+25%)',
                            'outline' => 'Draft or outline (+25%)',
                            'complex' => 'Specialist subject (+20%)',
                            'preferredWriter' => 'I will name a writer (+10%)',
                            'sources' => 'Copy of sources',
                            'samples' => 'Earlier samples ($5)',
                            'progressive' => 'Progressive delivery',
                            'vip' => 'VIP support ($9.99)',
                            'plagiarism' => 'Plagiarism report ($9.99)',
                        ] as $field => $label)
                            <label class="flex items-center gap-2 rounded-2xl border border-line bg-white px-4 py-3 text-sm">
                                <input type="checkbox" name="{{ $field === 'preferredWriter' ? 'preferred_writer' : $field }}" value="1" x-model="{{ $field }}">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label class="text-sm font-medium" for="coupon_code">Coupon <span class="font-normal text-ink/50">(optional)</span></label>
                    <input id="coupon_code" name="coupon_code" x-model="couponCode" @input="schedule()" class="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2" autocomplete="off">
                </div>

                <div>
                    <label class="text-sm font-medium" for="title">Working title</label>
                    <input id="title" name="title" value="{{ old('title') }}" required class="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-medium" for="brief">Brief</label>
                    <textarea id="brief" name="brief" required minlength="30" rows="7" class="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2" placeholder="Who will read this, what it must do, and anything we should not change.">{{ old('brief') }}</textarea>
                </div>
                @guest('customer')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium" for="name">Your name</label>
                            <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2">
                        </div>
                        <div>
                            <label class="text-sm font-medium" for="email">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-line bg-white px-3 py-2">
                        </div>
                    </div>
                @endguest
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="terms" value="1" required class="mt-1">
                    <span>I agree to the <a class="underline" href="{{ route('legal', 'terms') }}">terms</a>. This is not a request for student or academic work.</span>
                </label>
                @if ($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif
                <button class="rounded-full bg-navy px-5 py-3 text-sm font-semibold text-white" type="submit">Save this brief</button>
            </form>
        </div>

        <aside class="h-fit rounded-3xl bg-navy p-7 text-white lg:sticky lg:top-6" aria-live="polite">
            <p class="text-xs tracking-[0.16em] text-[#d7c4a3] uppercase">Quote</p>
            <p class="mt-4 font-display text-4xl" x-text="quote ? money(quote.amount_due) : (loading ? '…' : '$0.00')">$0.00</p>
            <p class="mt-2 text-sm text-white/60" x-show="loading">Updating…</p>
            <p class="mt-3 text-sm text-amber-100" x-show="error" x-text="error"></p>
            <ul class="mt-6 space-y-3 text-sm">
                <template x-for="line in (quote?.lines || [])" :key="line.key">
                    <li class="flex justify-between gap-4 border-t border-white/10 pt-3">
                        <span x-text="line.label"></span>
                        <span x-text="money(line.amount)"></span>
                    </li>
                </template>
            </ul>
            <template x-for="note in (quote?.notes || [])" :key="note">
                <p class="mt-4 text-xs leading-5 text-white/60" x-text="note"></p>
            </template>
            <p class="mt-6 text-xs leading-5 text-white/50">Prices are in US dollars, calculated on the server from the rate card. Writer pay is not shown here.</p>
        </aside>
    </section>
@endsection
