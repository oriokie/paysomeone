@extends('layouts.site')
@section('title', 'Start a brief — '.$siteName)
@section('description', 'Price an article, a resume, an edit, or a rewrite from the studio rate card before you send the brief.')

@section('content')
    <section class="wrap grid gap-10 py-14 lg:grid-cols-[1.15fr_0.85fr] lg:py-20" x-data="orderForm({ ...@js($options), initialStep: {{ $errors->any() ? 3 : 1 }} })">
        <div>
            <p class="eyebrow">Order</p>
            <h1 class="mt-3 font-display text-5xl text-navy">Start a brief</h1>
            <p class="lede mt-4">Three steps. The total is calculated on the server from the rate card.</p>
            @if ($readOnly)
                <p class="mt-5 rounded-2xl border border-wait/30 bg-wait-soft px-4 py-3 text-sm text-wait" role="status">Placing an order is blocked while this site is read-only. You can still see the price. Nothing is written to the live order book.</p>
            @endif

            <ol class="mt-8 flex gap-2 text-sm" aria-label="Progress">
                <template x-for="(label, index) in ['Service', 'Scope', 'Brief']" :key="label">
                    <li class="flex items-center gap-2">
                        <span class="grid size-7 place-items-center rounded-full text-xs font-semibold" :class="step === index + 1 ? 'bg-navy text-white' : 'bg-paper text-muted'" x-text="index + 1"></span>
                        <span class="hidden sm:inline" :class="step === index + 1 ? 'font-semibold text-navy' : 'text-muted'" x-text="label"></span>
                    </li>
                </template>
            </ol>

            <form method="POST" action="{{ route('order.store') }}" class="mt-8" @change="schedule()">
                @csrf
                <div x-show="step === 1" class="space-y-4">
                    <fieldset>
                        <legend class="label">What do you need?</legend>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <template x-for="(label, key) in options.services" :key="key">
                                <label class="card flex cursor-pointer items-start gap-3 p-4" :class="service === key ? 'border-navy ring-4 ring-navy/10' : ''">
                                    <input type="radio" name="service" :value="key" x-model="service" class="mt-1">
                                    <span class="text-sm font-medium" x-text="label"></span>
                                </label>
                            </template>
                        </div>
                    </fieldset>
                    <button class="btn btn-primary" type="button" @click="next()">Continue</button>
                </div>

                <div x-show="step === 2" x-cloak class="space-y-5">
                    <div>
                        <label class="label" for="deadline_id">Turnaround</label>
                        <select id="deadline_id" name="deadline_id" x-model="deadlineId" @change="schedule()" class="field">
                            <template x-for="deadline in deadlines" :key="deadline.id">
                                <option :value="deadline.id" x-text="deadline.name + ' — $' + (deadline.unit === 'word' ? Number(deadline.rate).toFixed(4) : Number(deadline.rate).toFixed(2)) + ' / ' + deadline.unit"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="usesWords">
                        <label class="label" for="words">Words</label>
                        <input id="words" name="words" type="number" min="100" max="20000" x-model.number="words" @input="schedule()" class="field">
                    </div>
                    <div x-show="!usesWords" class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="pages">Pages</label>
                            <input id="pages" name="pages" type="number" min="1" max="200" x-model.number="pages" @input="schedule()" class="field">
                        </div>
                        <div>
                            <label class="label" for="spacing">Spacing</label>
                            <select id="spacing" name="spacing" x-model="spacing" @change="schedule()" class="field">
                                <option value="double">Double (standard page rate)</option>
                                <option value="single">Single (twice the page rate)</option>
                            </select>
                        </div>
                    </div>
                    <fieldset>
                        <legend class="label">Writer</legend>
                        <div class="mt-3 space-y-2">
                            <template x-for="(tier, id) in options.tiers" :key="id">
                                <label class="card flex gap-3 p-4">
                                    <input type="radio" name="writer_category_id" :value="id" x-model.number="writerCategoryId">
                                    <span>
                                        <span class="block text-sm font-medium" x-text="tier.label"></span>
                                        <span class="block text-sm text-muted" x-text="tier.detail"></span>
                                    </span>
                                </label>
                            </template>
                        </div>
                    </fieldset>
                    <fieldset x-show="!usesWords">
                        <legend class="label">Extras</legend>
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
                                <label class="card flex items-center gap-2 px-4 py-3 text-sm">
                                    <input type="checkbox" name="{{ $field === 'preferredWriter' ? 'preferred_writer' : $field }}" value="1" x-model="{{ $field }}">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div>
                        <label class="label" for="coupon_code">Coupon <span class="font-normal text-muted">(optional)</span></label>
                        <input id="coupon_code" name="coupon_code" x-model="couponCode" @input="schedule()" class="field" autocomplete="off">
                    </div>
                    <div class="flex gap-3">
                        <button class="btn btn-secondary" type="button" @click="back()">Back</button>
                        <button class="btn btn-primary" type="button" @click="next()">Continue</button>
                    </div>
                </div>

                <div x-show="step === 3" x-cloak class="space-y-5">
                    <div>
                        <label class="label" for="title">Working title</label>
                        <input id="title" name="title" value="{{ old('title') }}" required class="field">
                    </div>
                    <div>
                        <label class="label" for="brief">Brief</label>
                        <textarea id="brief" name="brief" required minlength="30" rows="7" class="field" placeholder="Who will read this, what it must do, and anything we should not change.">{{ old('brief') }}</textarea>
                    </div>
                    @guest('customer')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label" for="name">Your name</label>
                                <input id="name" name="name" value="{{ old('name') }}" required class="field">
                            </div>
                            <div>
                                <label class="label" for="email">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="field">
                            </div>
                        </div>
                    @endguest
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="terms" value="1" required class="mt-1">
                        <span>I agree to the <a class="font-semibold text-accent" href="{{ route('legal', 'terms') }}">terms</a>. This is not a request for student or academic work.</span>
                    </label>
                    @if ($errors->any())
                        <p class="rounded-2xl bg-bad-soft px-4 py-3 text-sm text-bad" role="alert">{{ $errors->first() }}</p>
                    @endif
                    <div class="flex flex-wrap gap-3">
                        <button class="btn btn-secondary" type="button" @click="back()">Back</button>
                        <button class="btn btn-primary" type="submit" name="intent" value="pay">Place order</button>
                        <button class="btn btn-secondary" type="submit" name="intent" value="inquiry">Save as an enquiry</button>
                    </div>
                </div>
            </form>
        </div>

        <aside class="quote-card h-fit rounded-3xl p-7 text-white lg:sticky lg:top-28" aria-live="polite">
            <p class="text-xs font-semibold tracking-[0.16em] text-accent-bright uppercase">Live quote</p>
            <p class="mt-4 font-display text-5xl" x-text="quote ? money(quote.amount_due) : (loading ? '…' : '$0.00')">$0.00</p>
            <p class="mt-2 text-sm text-white/60" x-show="loading">Updating from the rate card…</p>
            <p class="mt-3 text-sm text-accent-bright" x-show="error" x-text="error"></p>
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
            <p class="mt-6 text-xs leading-5 text-white/50">US dollars, calculated on the server. Writer pay is not shown.</p>
        </aside>
    </section>
@endsection
