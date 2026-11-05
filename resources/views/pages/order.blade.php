@extends('layouts.checkout')
@section('title', 'Place an order — '.$siteName)
@section('description', 'Article writing, resumes, editing, rewriting, and programming. The same fields as the current order desk, without student or academic options.')

@section('content')
    @php
        $serviceCopy = [
            'articles' => ['Posts, pages, and newsletters, priced by the word.', 'pen'],
            'resumes' => ['Profiles and resumes, priced by the page.', 'user'],
            'editing' => ['A finished draft, cleaned up.', 'check'],
            'rewriting' => ['A new draft from an existing one.', 'refresh'],
            'software' => ['Spreadsheets, scripts, and site work.', 'code'],
        ];
        $extras = [
            'proofreading' => ['Expert proofreading', 'A second pass on grammar and consistency.', '+25%'],
            'outline' => ['Draft outline', 'A structure before the full draft.', '+25%'],
            'complex' => ['Complex assignment', 'Specialist or technical subject.', '+20%'],
            'preferredWriter' => ['Preferred writer', 'You will name the writer.', '+10%'],
            'sources' => ['Copy of sources', 'The sources used, with a $14.95 minimum.', '10%'],
            'samples' => ['Writer samples', 'Earlier samples before the full draft.', '$5'],
            'progressive' => ['Progressive delivery', 'Part of the work before the deadline.', '10%'],
            'vip' => ['VIP support', 'A faster reply from support.', '$9.99'],
            'plagiarism' => ['Plagiarism report', 'A report with the delivery.', '$9.99'],
        ];
    @endphp
    <div class="mx-auto max-w-[1200px] px-5 py-8 pb-28 lg:py-12 lg:pb-16" x-data="orderForm(@js($options))">
        <div class="mb-8 flex items-end justify-between gap-6">
            <div>
                <p class="text-[13px] font-medium text-slate-500">Order</p>
                <h1 class="mt-1 font-display text-[32px] tracking-tight text-navy">Place an order</h1>
            </div>
            <p class="hidden text-[13px] text-slate-500 sm:block">Step <span x-text="step"></span> of 5</p>
        </div>

        <ol class="mb-8 flex gap-2" aria-label="Progress">
            <template x-for="(label, index) in steps" :key="label">
                <li class="min-w-0 flex-1">
                    <button type="button" class="flex w-full flex-col gap-2 text-left" :disabled="index + 1 > maxReached" @click="go(index + 1)">
                        <span class="h-1 rounded-full" :class="step >= index + 1 ? 'bg-navy' : 'bg-slate-200'"></span>
                        <span class="hidden truncate text-[13px] sm:block" :class="step === index + 1 ? 'font-semibold text-navy' : 'text-slate-400'" x-text="label"></span>
                    </button>
                </li>
            </template>
        </ol>

        @if ($errors->any())
            <p class="mb-6 rounded-2xl bg-bad-soft px-4 py-3 text-[15px] text-bad" role="alert">{{ $errors->first() }}</p>
        @endif

        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
            <form method="POST" action="{{ route('order.store') }}" enctype="multipart/form-data" class="max-w-[720px]" @change="schedule()">
                @csrf
                <input type="hidden" name="gmt" value="0">

                <section x-show="step === 1" class="space-y-4">
                    <h2 class="font-display text-2xl tracking-tight text-navy">What do you need?</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($options['services'] as $key => $label)
                            <label class="ui-card flex cursor-pointer gap-4 p-5 transition duration-150 hover:-translate-y-0.5" :class="service === '{{ $key }}' ? 'ring-2 ring-navy' : ''">
                                <input class="sr-only" type="radio" name="service" value="{{ $key }}" x-model="service">
                                <span>
                                    <span class="flex items-center gap-2 font-display text-lg tracking-tight text-navy">
                                        <x-glyph :name="$serviceCopy[$key][1] ?? 'pen'" class="size-5 text-slate-500" />
                                        {{ $label }}
                                    </span>
                                    <span class="mt-1 block text-[13px] leading-5 text-slate-500">{{ $serviceCopy[$key][0] ?? '' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section x-show="step === 2" x-cloak class="space-y-8">
                    <h2 class="font-display text-2xl tracking-tight text-navy">Details</h2>

                    <div x-show="usesWords">
                        <p class="ui-label">Type of content</p>
                        <input class="ui-input mb-3" x-model="typeQuery" placeholder="Search types" autocomplete="off" aria-label="Search content types">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($options['catalog']['articles'] as $type)
                                <label class="cursor-pointer rounded-full border px-3 py-2 text-[13px]" x-show='!typeQuery || {{ \Illuminate\Support\Js::from(strtolower($type['title'])) }}.includes(typeQuery.toLowerCase())' :class="chipOn(String(paperTypeId) === '{{ $type['id'] }}')">
                                    <input class="sr-only" type="radio" name="paper_type_id" value="{{ $type['id'] }}" x-model="paperTypeId">
                                    {{ $type['title'] }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div x-show="!usesWords" x-cloak>
                        <p class="ui-label">Document type</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($options['catalog']['documents'] as $type)
                                <label class="cursor-pointer rounded-full border px-3 py-2 text-[13px]" :class="chipOn(String(paperTypeId) === '{{ $type['id'] }}')">
                                    <input class="sr-only" type="radio" name="paper_type_id" value="{{ $type['id'] }}" x-model="paperTypeId">
                                    {{ $type['title'] }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div x-show="isSoftware" x-cloak>
                        <label class="ui-label" for="software">Software</label>
                        <input id="software" name="software" x-model="software" class="ui-input" list="software-list" maxlength="255" placeholder="Excel, Python, WordPress">
                        <datalist id="software-list">
                            <template x-for="item in catalog.software" :key="item.id"><option :value="item.title"></option></template>
                        </datalist>
                    </div>
                    <div x-show="isSoftware && catalog.deliverables.length" x-cloak>
                        <label class="ui-label" for="deliverable_id">Deliverable</label>
                        <select id="deliverable_id" name="deliverable_id" x-model="deliverableId" class="ui-input">
                            <template x-for="item in catalog.deliverables" :key="item.id">
                                <option :value="item.id" x-text="item.title"></option>
                            </template>
                        </select>
                    </div>

                    <fieldset>
                        <legend class="ui-label">English type</legend>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="item in catalog.englishTypes" :key="item.id">
                                <label class="cursor-pointer rounded-full border px-3 py-2 text-[13px]" :class="chipOn(String(englishTypeId) === String(item.id))">
                                    <input class="sr-only" type="radio" name="english_type_id" :value="item.id" x-model="englishTypeId">
                                    <span x-text="item.title"></span>
                                </label>
                            </template>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class="ui-label">Target audience</legend>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="item in catalog.audiences" :key="item.id">
                                <label class="cursor-pointer rounded-full border px-3 py-2 text-[13px]" :class="chipOn(String(targetAudienceId) === String(item.id))">
                                    <input class="sr-only" type="radio" name="target_audience_id" :value="item.id" x-model="targetAudienceId">
                                    <span x-text="item.title"></span>
                                </label>
                            </template>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class="ui-label">Writing tone</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <template x-for="item in catalog.tones" :key="item.id">
                                <label class="ui-card cursor-pointer p-4" :class="String(writingToneId) === String(item.id) ? 'ring-2 ring-navy' : ''">
                                    <input class="sr-only" type="radio" name="writing_tone_id" :value="item.id" x-model="writingToneId">
                                    <span class="block text-[15px] font-medium" x-text="item.title"></span>
                                    <span class="mt-1 block text-[13px] text-slate-500" x-show="item.description" x-text="item.description"></span>
                                </label>
                            </template>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class="ui-label">Writing style</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <template x-for="item in catalog.styles" :key="item.id">
                                <label class="ui-card cursor-pointer p-4" :class="String(writingStyleId) === String(item.id) ? 'ring-2 ring-navy' : ''">
                                    <input class="sr-only" type="radio" name="writing_style_id" :value="item.id" x-model="writingStyleId">
                                    <span class="block text-[15px] font-medium" x-text="item.title"></span>
                                    <span class="mt-1 block text-[13px] text-slate-500" x-show="item.description" x-text="item.description"></span>
                                </label>
                            </template>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend class="ui-label">Content feel</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <template x-for="item in catalog.feels" :key="item.id">
                                <label class="ui-card cursor-pointer p-4" :class="String(contentFeelId) === String(item.id) ? 'ring-2 ring-navy' : ''">
                                    <input class="sr-only" type="radio" name="content_feel_id" :value="item.id" x-model="contentFeelId">
                                    <span class="block text-[15px] font-medium" x-text="item.title"></span>
                                    <span class="mt-1 block text-[13px] text-slate-500" x-show="item.description" x-text="item.description"></span>
                                </label>
                            </template>
                        </div>
                    </fieldset>

                    <div>
                        <label class="ui-label" for="title">Title</label>
                        <input id="title" name="title" x-model="title" required maxlength="180" class="ui-input" :class="fieldErrors.title && 'border-bad'">
                        <p class="mt-2 text-[13px] text-bad" x-show="fieldErrors.title" x-text="fieldErrors.title"></p>
                    </div>
                    <div>
                        <label class="ui-label" for="brief">Additional information</label>
                        <textarea id="brief" name="brief" x-model="brief" required minlength="30" maxlength="20000" rows="8" class="ui-input h-auto py-3" :class="fieldErrors.brief && 'border-bad'" placeholder="Who will read this, what it must do, and anything that must not change."></textarea>
                        <div class="mt-2 flex justify-between text-[13px]">
                            <p class="text-bad" x-show="fieldErrors.brief" x-text="fieldErrors.brief"></p>
                            <p class="ml-auto tabular-nums text-slate-400"><span x-text="(brief || '').length"></span> / 20000</p>
                        </div>
                    </div>
                    <div x-show="usesWords">
                        <label class="ui-label" for="keywords">Suggested keywords <span class="font-normal text-slate-400">Optional</span></label>
                        <input id="keywords" name="keywords" x-model="keywords" maxlength="255" class="ui-input">
                    </div>
                    <div>
                        <label class="ui-label" for="attachments">Attachments <span class="font-normal text-slate-400">Optional</span></label>
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center" @dragover.prevent @drop.prevent="takeFiles($event.dataTransfer.files); $refs.files.files = $event.dataTransfer.files">
                            <p class="text-[15px] text-slate-600">Drop files here, or choose them</p>
                            <p class="ui-hint">Saved on the order after it is placed. pdf, doc, txt, png, jpg, zip.</p>
                            <input id="attachments" x-ref="files" class="mt-3 text-[13px]" type="file" name="attachments[]" multiple @change="takeFiles($event.target.files)">
                        </div>
                        <ul class="mt-2 text-[13px] text-slate-500">
                            <template x-for="name in files" :key="name"><li x-text="name"></li></template>
                        </ul>
                    </div>
                </section>

                <section x-show="step === 3" x-cloak class="space-y-8">
                    <h2 class="font-display text-2xl tracking-tight text-navy">Deadline and length</h2>
                    <fieldset>
                        <legend class="ui-label">Deadline</legend>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <template x-for="item in deadlines" :key="item.id">
                                <label class="ui-card cursor-pointer p-4" :class="String(deadlineId) === String(item.id) ? 'ring-2 ring-navy' : ''">
                                    <input class="sr-only" type="radio" name="deadline_id" :value="item.id" x-model="deadlineId" @change="schedule()">
                                    <span class="block font-display text-lg tracking-tight" x-text="item.name"></span>
                                    <span class="mt-1 block text-[13px] tabular-nums text-slate-500" x-text="rateLabel(item)"></span>
                                </label>
                            </template>
                        </div>
                        <p class="ui-hint" x-show="dueLabel">Due <span x-text="dueLabel"></span>.</p>
                        <p class="mt-2 text-[13px] text-bad" x-show="fieldErrors.deadline" x-text="fieldErrors.deadline"></p>
                    </fieldset>

                    <div x-show="usesWords">
                        <label class="ui-label" for="words">Words</label>
                        <div class="flex h-12 max-w-xs items-center rounded-xl border border-slate-200 bg-white">
                            <button class="h-12 w-12 text-lg" type="button" @click="nudge('words', -50, 100, 20000)" aria-label="Fewer words">−</button>
                            <input id="words" name="words" type="number" min="100" max="20000" x-model.number="words" @input="schedule()" class="h-12 w-full border-0 bg-transparent text-center text-[15px] tabular-nums outline-none">
                            <button class="h-12 w-12 text-lg" type="button" @click="nudge('words', 50, 100, 20000)" aria-label="More words">+</button>
                        </div>
                        <p class="ui-hint" x-text="pageHint"></p>
                        <p class="mt-2 text-[13px] text-bad" x-show="fieldErrors.words" x-text="fieldErrors.words"></p>
                    </div>

                    <div x-show="!usesWords" class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="ui-label" for="pages">Pages</label>
                            <div class="flex h-12 items-center rounded-xl border border-slate-200 bg-white">
                                <button class="h-12 w-10" type="button" @click="nudge('pages', -1, 1, 200)" aria-label="Fewer pages">−</button>
                                <input id="pages" name="pages" type="number" min="1" max="200" x-model.number="pages" @input="schedule()" class="h-12 w-full border-0 text-center tabular-nums outline-none">
                                <button class="h-12 w-10" type="button" @click="nudge('pages', 1, 1, 200)" aria-label="More pages">+</button>
                            </div>
                            <p class="mt-2 text-[13px] text-bad" x-show="fieldErrors.pages" x-text="fieldErrors.pages"></p>
                        </div>
                        <div x-show="isPages">
                            <label class="ui-label" for="slides">Slides <span class="font-normal text-slate-400">Optional</span></label>
                            <input id="slides" name="slides" type="number" min="0" max="100" x-model.number="slides" @input="schedule()" class="ui-input">
                        </div>
                        <div x-show="isPages">
                            <label class="ui-label" for="charts">Charts <span class="font-normal text-slate-400">Optional</span></label>
                            <input id="charts" name="charts" type="number" min="0" max="100" x-model.number="charts" @input="schedule()" class="ui-input">
                        </div>
                    </div>

                    <fieldset x-show="isPages">
                        <legend class="ui-label">Spacing</legend>
                        <div class="inline-flex rounded-full bg-slate-100 p-1">
                            <label class="cursor-pointer rounded-full px-4 py-2 text-[13px]" :class="spacing === 'double' ? 'bg-white font-semibold shadow-sm' : 'text-slate-500'">
                                <input class="sr-only" type="radio" name="spacing" value="double" x-model="spacing" @change="schedule()"> Double
                            </label>
                            <label class="cursor-pointer rounded-full px-4 py-2 text-[13px]" :class="spacing === 'single' ? 'bg-white font-semibold shadow-sm' : 'text-slate-500'">
                                <input class="sr-only" type="radio" name="spacing" value="single" x-model="spacing" @change="schedule()"> Single
                            </label>
                        </div>
                    </fieldset>
                    <div x-show="isPages">
                        <label class="ui-label" for="sources_count">Sources <span class="font-normal text-slate-400">Optional</span></label>
                        <input id="sources_count" name="sources_count" type="number" min="0" max="100" x-model.number="sourcesCount" class="ui-input max-w-[8rem]">
                    </div>

                    <fieldset>
                        <legend class="ui-label">Writer</legend>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <template x-for="(tier, id) in options.tiers" :key="id">
                                <label class="ui-card cursor-pointer p-4" :class="String(writerCategoryId) === String(id) ? 'ring-2 ring-navy' : ''">
                                    <input class="sr-only" type="radio" name="writer_category_id" :value="id" x-model.number="writerCategoryId" @change="schedule()">
                                    <span class="block font-display text-lg tracking-tight" x-text="tier.short || tier.label"></span>
                                    <span class="mt-1 block text-[13px] tabular-nums text-slate-500" x-text="tier.percent ? '+' + tier.percent + '%' : 'Included'"></span>
                                    <span class="mt-2 block text-[13px] leading-5 text-slate-500" x-text="tier.detail"></span>
                                </label>
                            </template>
                        </div>
                    </fieldset>
                </section>

                <section x-show="step === 4" x-cloak class="space-y-6">
                    <h2 class="font-display text-2xl tracking-tight text-navy">Extras</h2>
                    <div x-show="isPages" class="grid gap-3">
                        @foreach ($extras as $field => $meta)
                            <label class="ui-card flex cursor-pointer items-start gap-4 p-4">
                                <input type="checkbox" name="{{ $field === 'preferredWriter' ? 'preferred_writer' : $field }}" value="1" x-model="{{ $field }}" @change="schedule()" class="mt-1">
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-3">
                                        <span class="text-[15px] font-medium">{{ $meta[0] }}</span>
                                        <span class="text-[13px] tabular-nums text-slate-500">{{ $meta[2] }}</span>
                                    </span>
                                    <span class="mt-1 block text-[13px] text-slate-500">{{ $meta[1] }}</span>
                                    @if ($field === 'progressive')
                                        <span class="mt-2 inline-flex rounded-full bg-good-soft px-2 py-0.5 text-[13px] font-medium text-good" x-show="quote?.progressive?.forced">Included at this size and deadline</span>
                                        <span class="mt-2 block text-[13px] text-slate-400" x-show="quote?.progressive && !quote.progressive.available && !quote.progressive.forced" x-text="quote?.progressive?.reason"></span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[15px] text-slate-500" x-show="!isPages">Page extras apply to resumes, editing, and rewriting. A coupon still works here.</p>
                    <div>
                        <button class="text-[15px] font-medium text-navy underline-offset-4 hover:underline" type="button" x-show="!couponOpen" @click="couponOpen = true">Have a code?</button>
                        <div x-show="couponOpen" x-cloak>
                            <label class="ui-label" for="coupon_code">Coupon <span class="font-normal text-slate-400">Optional</span></label>
                            <input id="coupon_code" name="coupon_code" x-model="couponCode" @input="schedule()" class="ui-input max-w-xs" autocomplete="off">
                            <p class="mt-2 text-[13px] text-good" x-show="couponCode && quote && Number(quote.discount) !== 0">Code applied.</p>
                        </div>
                    </div>
                </section>

                <section x-show="step === 5" x-cloak class="space-y-6">
                    <h2 class="font-display text-2xl tracking-tight text-navy">Review</h2>
                    <dl class="ui-card divide-y divide-slate-100">
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div><dt class="text-[13px] text-slate-500">Service</dt><dd class="text-[15px]" x-text="serviceLabel"></dd></div>
                            <button class="text-[13px] font-medium text-navy" type="button" @click="go(1)">Edit</button>
                        </div>
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div><dt class="text-[13px] text-slate-500">Title</dt><dd class="text-[15px]" x-text="title || '—'"></dd><dd class="mt-1 line-clamp-3 text-[13px] text-slate-500" x-text="brief"></dd></div>
                            <button class="text-[13px] font-medium text-navy" type="button" @click="go(2)">Edit</button>
                        </div>
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div><dt class="text-[13px] text-slate-500">Deadline and length</dt><dd class="text-[15px]" x-text="(deadline ? deadline.name : '—') + ' · ' + quantityLabel"></dd><dd class="text-[13px] text-slate-500" x-show="dueLabel" x-text="'Due ' + dueLabel"></dd></div>
                            <button class="text-[13px] font-medium text-navy" type="button" @click="go(3)">Edit</button>
                        </div>
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div><dt class="text-[13px] text-slate-500">Total</dt><dd class="text-[15px] tabular-nums" x-text="quote ? money(quote.amount_due) : '—'"></dd></div>
                            <button class="text-[13px] font-medium text-navy" type="button" @click="go(4)">Edit</button>
                        </div>
                    </dl>
                    @guest('customer')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="ui-label" for="name">Your name</label>
                                <input id="name" name="name" x-model="name" required class="ui-input" autocomplete="name">
                            </div>
                            <div>
                                <label class="ui-label" for="email">Email</label>
                                <input id="email" name="email" type="email" x-model="email" required class="ui-input" autocomplete="email">
                            </div>
                        </div>
                    @endguest
                    <label class="flex items-start gap-3 text-[15px]">
                        <input class="mt-1" type="checkbox" name="terms" value="1" required>
                        <span>I agree to the <a class="font-semibold text-accent" href="{{ route('legal', 'terms') }}">terms</a>. This order is not a request for student or academic work.</span>
                    </label>
                    @if ($readOnly)
                        <p class="rounded-2xl bg-wait-soft px-4 py-3 text-[15px] text-wait" role="status">Placing an order is paused on this preview. The price is real. Nothing is written to the live order book.</p>
                    @else
                        <div class="flex flex-wrap gap-3">
                            <button class="ui-btn ui-btn-primary" type="submit" name="intent" value="pay">Place order</button>
                            <button class="ui-btn ui-btn-quiet" type="submit" name="intent" value="inquiry">Save as an enquiry</button>
                        </div>
                    @endif
                    <ul class="flex flex-wrap gap-x-6 gap-y-2 text-[13px] text-slate-500">
                        <li class="inline-flex items-center gap-2"><x-glyph name="lock" class="size-4" /> Payment is taken on the order, after it exists.</li>
                        <li class="inline-flex items-center gap-2"><x-glyph name="refresh" class="size-4" /> A revision can be requested once the work is delivered.</li>
                        <li class="inline-flex items-center gap-2"><x-glyph name="shield" class="size-4" /> The brief stays on your account.</li>
                    </ul>
                </section>

                <div class="mt-8 hidden items-center gap-3 lg:flex">
                    <button class="ui-btn ui-btn-quiet" type="button" x-show="step > 1" @click="back()">Back</button>
                    <button class="ui-btn ui-btn-primary" type="button" x-show="step < 5" @click="next()">Continue</button>
                </div>
            </form>

            <aside class="ui-card sticky top-24 hidden p-6 lg:block" aria-live="polite">
                @include('pages.partials.order-summary')
            </aside>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200/80 bg-white/95 px-4 py-3 backdrop-blur lg:hidden">
            <div class="flex items-center justify-between gap-3">
                <button type="button" class="text-left" @click="summaryOpen = !summaryOpen">
                    <span class="block text-[13px] text-slate-500">Total</span>
                    <span class="font-display text-xl tabular-nums tracking-tight" x-text="quote ? money(quote.amount_due) : '$0.00'"></span>
                </button>
                <button class="ui-btn ui-btn-quiet h-11 px-4" type="button" x-show="step > 1" @click="back()">Back</button>
                <button class="ui-btn ui-btn-primary h-11" type="button" x-show="step < 5" @click="next()">Continue</button>
            </div>
            <div class="mt-4 max-h-[60vh] overflow-auto" x-show="summaryOpen" x-cloak x-transition>
                @include('pages.partials.order-summary')
            </div>
        </div>
    </div>
@endsection
