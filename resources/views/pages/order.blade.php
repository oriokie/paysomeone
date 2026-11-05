@extends('layouts.site')
@section('title', 'Place an order — '.$siteName)
@section('description', 'Article writing, resumes, editing, rewriting, and programming. The same fields the current order desk stores, without student or academic options.')

@section('content')
    <section class="wrap grid gap-10 py-12 lg:grid-cols-[minmax(0,1fr)_20rem] lg:py-16" x-data="orderForm(@js($options))">
        <div>
            <p class="eyebrow">Order</p>
            <h1 class="mt-3 font-display text-4xl text-navy sm:text-5xl">Place an order</h1>
            <p class="lede mt-4">The same sections as the current order form: service, document, deadline, writer, and extras. Academic level, citation style, subject, and student paper types are not on this form.</p>
            @if ($readOnly)
                <p class="mt-5 rounded-2xl border border-wait/30 bg-wait-soft px-4 py-3 text-sm text-wait" role="status">Placing an order is blocked while this site is read-only. You can still see the price. Nothing is written to the live order book.</p>
            @endif

            <form method="POST" action="{{ route('order.store') }}" class="card mt-8 px-5 sm:px-7" @change="schedule()">
                @csrf
                <input type="hidden" name="gmt" value="0">

                <fieldset class="form-row">
                    <legend class="label sm:pt-2">Service</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <template x-for="(label, key) in options.services" :key="key">
                            <label class="choice">
                                <input type="radio" name="service" :value="key" x-model="service" class="mt-1">
                                <span class="font-medium" x-text="label"></span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <div class="form-row" x-show="paperTypes.length" x-cloak>
                    <label class="label sm:pt-2" for="paper_type_id" x-text="usesWords ? 'Type of content' : 'Document type'"></label>
                    <select id="paper_type_id" name="paper_type_id" x-model="paperTypeId" class="field">
                        <template x-for="type in paperTypes" :key="type.id">
                            <option :value="type.id" x-text="type.title"></option>
                        </template>
                    </select>
                </div>

                <div class="form-row" x-show="isSoftware" x-cloak>
                    <label class="label sm:pt-2" for="software">Software</label>
                    <div>
                        <input id="software" name="software" x-model="software" class="field" list="software-list" maxlength="255" placeholder="Excel, Python, WordPress…">
                        <datalist id="software-list">
                            <template x-for="item in catalog.software" :key="item.id">
                                <option :value="item.title"></option>
                            </template>
                        </datalist>
                    </div>
                </div>

                <div class="form-row" x-show="isSoftware && catalog.deliverables.length" x-cloak>
                    <label class="label sm:pt-2" for="deliverable_id">Deliverable</label>
                    <select id="deliverable_id" name="deliverable_id" x-model="deliverableId" class="field">
                        <template x-for="item in catalog.deliverables" :key="item.id">
                            <option :value="item.id" x-text="item.title"></option>
                        </template>
                    </select>
                </div>

                <div class="form-row">
                    <label class="label sm:pt-2" for="title">Title</label>
                    <input id="title" name="title" value="{{ old('title') }}" required maxlength="180" class="field">
                </div>

                <div class="form-row">
                    <label class="label sm:pt-2" for="brief">Additional information</label>
                    <textarea id="brief" name="brief" required minlength="30" rows="6" class="field" placeholder="Who will read this, what it must do, and anything that must not change.">{{ old('brief') }}</textarea>
                </div>

                <div class="form-row" x-show="usesWords" x-cloak>
                    <label class="label sm:pt-2" for="keywords">Suggested keywords</label>
                    <input id="keywords" name="keywords" value="{{ old('keywords') }}" maxlength="255" class="field">
                </div>

                <div class="form-row">
                    <label class="label sm:pt-2" for="deadline_id">Deadline</label>
                    <select id="deadline_id" name="deadline_id" x-model="deadlineId" @change="schedule()" class="field">
                        <template x-for="deadline in deadlines" :key="deadline.id">
                            <option :value="deadline.id" x-text="deadline.name"></option>
                        </template>
                    </select>
                </div>

                <div class="form-row" x-show="usesWords" x-cloak>
                    <label class="label sm:pt-2" for="words">Words</label>
                    <input id="words" name="words" type="number" min="100" max="20000" x-model.number="words" @input="schedule()" class="field">
                </div>

                <div class="form-row" x-show="!usesWords" x-cloak>
                    <label class="label sm:pt-2" for="pages" x-text="isSoftware ? 'Pages' : 'Pages'"></label>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <input id="pages" name="pages" type="number" min="1" max="200" x-model.number="pages" @input="schedule()" class="field" aria-label="Pages">
                        <input name="slides" type="number" min="0" max="100" x-model.number="slides" @input="schedule()" class="field" aria-label="Slides" x-show="isPages">
                        <input name="charts" type="number" min="0" max="100" x-model.number="charts" @input="schedule()" class="field" aria-label="Charts" x-show="isPages">
                    </div>
                </div>

                <div class="form-row" x-show="isPages" x-cloak>
                    <label class="label sm:pt-2" for="spacing">Spacing</label>
                    <select id="spacing" name="spacing" x-model="spacing" @change="schedule()" class="field">
                        <option value="double">Double</option>
                        <option value="single">Single</option>
                    </select>
                </div>

                <div class="form-row" x-show="isPages" x-cloak>
                    <label class="label sm:pt-2" for="sources_count">Sources</label>
                    <input id="sources_count" name="sources_count" type="number" min="0" max="100" value="0" class="field">
                </div>

                <fieldset class="form-row">
                    <legend class="label sm:pt-2">Writer category</legend>
                    <div class="grid gap-2">
                        <template x-for="(tier, id) in options.tiers" :key="id">
                            <label class="choice">
                                <input type="radio" name="writer_category_id" :value="id" x-model.number="writerCategoryId" @change="schedule()">
                                <span>
                                    <span class="block font-medium" x-text="tier.label"></span>
                                    <span class="block text-muted" x-text="tier.detail"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <fieldset class="form-row" x-show="usesWords && catalog.englishTypes.length" x-cloak>
                    <legend class="label sm:pt-2">English type</legend>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="item in catalog.englishTypes" :key="item.id">
                            <label class="choice">
                                <input type="radio" name="english_type_id" :value="item.id" x-model="englishTypeId">
                                <span x-text="item.title"></span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <fieldset class="form-row" x-show="usesWords && catalog.audiences.length" x-cloak>
                    <legend class="label sm:pt-2">Target audience</legend>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="item in catalog.audiences" :key="item.id">
                            <label class="choice">
                                <input type="radio" name="target_audience_id" :value="item.id" x-model="targetAudienceId">
                                <span x-text="item.title"></span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <fieldset class="form-row" x-show="usesWords && catalog.tones.length" x-cloak>
                    <legend class="label sm:pt-2">Writing tone</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <template x-for="item in catalog.tones" :key="item.id">
                            <label class="choice">
                                <input type="radio" name="writing_tone_id" :value="item.id" x-model="writingToneId">
                                <span>
                                    <span class="block font-medium" x-text="item.title"></span>
                                    <span class="block text-muted" x-show="item.description" x-text="item.description"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <fieldset class="form-row" x-show="usesWords && catalog.styles.length" x-cloak>
                    <legend class="label sm:pt-2">Writing style</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <template x-for="item in catalog.styles" :key="item.id">
                            <label class="choice">
                                <input type="radio" name="writing_style_id" :value="item.id" x-model="writingStyleId">
                                <span>
                                    <span class="block font-medium" x-text="item.title"></span>
                                    <span class="block text-muted" x-show="item.description" x-text="item.description"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <fieldset class="form-row" x-show="usesWords && catalog.feels.length" x-cloak>
                    <legend class="label sm:pt-2">Content feel</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <template x-for="item in catalog.feels" :key="item.id">
                            <label class="choice">
                                <input type="radio" name="content_feel_id" :value="item.id" x-model="contentFeelId">
                                <span>
                                    <span class="block font-medium" x-text="item.title"></span>
                                    <span class="block text-muted" x-show="item.description" x-text="item.description"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <fieldset class="form-row" x-show="isPages" x-cloak>
                    <legend class="label sm:pt-2">Additional services</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ([
                            'proofreading' => 'Expert proofreading',
                            'outline' => 'Draft outline',
                            'complex' => 'Complex assignment',
                            'preferredWriter' => 'Preferred writer',
                            'sources' => 'Copy of sources',
                            'samples' => 'Writer samples',
                            'progressive' => 'Progressive delivery',
                            'vip' => 'VIP support',
                            'plagiarism' => 'Plagiarism report',
                        ] as $field => $label)
                            <label class="choice">
                                <input type="checkbox" name="{{ $field === 'preferredWriter' ? 'preferred_writer' : $field }}" value="1" x-model="{{ $field }}" @change="schedule()">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="form-row">
                    <label class="label sm:pt-2" for="coupon_code">Coupon</label>
                    <input id="coupon_code" name="coupon_code" x-model="couponCode" @input="schedule()" class="field" autocomplete="off">
                </div>

                @guest('customer')
                    <div class="form-row">
                        <label class="label sm:pt-2" for="name">Your name</label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input id="name" name="name" value="{{ old('name') }}" required class="field" autocomplete="name">
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="field" autocomplete="email" aria-label="Email">
                        </div>
                    </div>
                @endguest

                <div class="py-5">
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="terms" value="1" required class="mt-1">
                        <span>I agree to the <a class="font-semibold text-accent" href="{{ route('legal', 'terms') }}">terms</a>. This order is not a request for student or academic work.</span>
                    </label>
                    @if ($errors->any())
                        <p class="mt-4 rounded-2xl bg-bad-soft px-4 py-3 text-sm text-bad" role="alert">{{ $errors->first() }}</p>
                    @endif
                    <div class="mt-5 flex flex-wrap gap-3">
                        <button class="btn btn-primary" type="submit" name="intent" value="pay">Place order</button>
                        <button class="btn btn-secondary" type="submit" name="intent" value="inquiry">Save as an enquiry</button>
                    </div>
                </div>
            </form>
        </div>

        <aside class="quote-card h-fit rounded-3xl p-7 text-white lg:sticky lg:top-28" aria-live="polite">
            <p class="text-xs font-semibold tracking-[0.16em] text-accent-bright uppercase">Order summary</p>
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
            <p class="mt-6 text-xs leading-5 text-white/50">US dollars, calculated on the server. Payment is taken on the order page after the order is filed.</p>
        </aside>
    </section>
@endsection
