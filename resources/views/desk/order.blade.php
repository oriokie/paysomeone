@extends('layouts.desk')
@section('title', 'Order #'.$order->order_id.' — '.$siteName)
@section('heading', 'Order #'.$order->order_id)

@section('content')
    @php
        use App\Models\Order;
        use App\Support\Departments;

        $due = max(0, (float) $order->total - (float) $order->total_paid);
        $tab = session('tab', request('tab', 'overview'));
        $payPhase = session('pay_phase', 'choose');
        $tabs = ['overview' => 'Overview', 'files' => 'Files', 'messages' => 'Messages', 'bids' => 'Bids', 'revision' => 'Revisions', 'payment' => 'Payment'];
        $customerFiles = $order->files->filter(fn ($file) => (int) $file->uploader_id === (int) $order->customer_id);
        $writerFiles = $order->files->filter(fn ($file) => (int) $file->uploader_id !== (int) $order->customer_id);
        $showWriterFiles = in_array((int) $order->status_id, [Order::DELIVERED, Order::FINISHED], true);
        $bids = $order->bids->where('is_deleted', false);
        $progress = ['Placed', 'Paid', 'Writer assigned', 'In progress', 'Delivered', 'Finished'];
        $status = (int) $order->status_id;
        $progressAt = match (true) {
            $status === Order::FINISHED => 5,
            $status === Order::DELIVERED => 4,
            in_array($status, [Order::WRITER_ACCEPTED, Order::DONE, Order::REVISION], true) => 3,
            (bool) $order->writer_id || $status === Order::WRITER_ASSIGNED => 2,
            (bool) $order->is_paid || in_array($status, [Order::NEW_PAID, Order::ORDER_PUBLISHED], true) => 1,
            default => 0,
        };
        $primary = match (true) {
            $due > 0 => ['Pay now', 'payment'],
            $status === Order::DELIVERED => ['Request revision', 'revision'],
            $status === Order::FINISHED => ['View files', 'files'],
            default => ['Messages', 'messages'],
        };
    @endphp

    <div id="order-screen" x-data="{ tab: @js($tab) }">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[13px] text-slate-500">Order #{{ $order->order_id }} · {{ $order->serviceLabel() }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h2 class="font-display text-[32px] tracking-tight text-navy">{{ $order->title }}</h2>
                    <x-pill :status="$order->status_id" :label="$order->statusLabel()" />
                </div>
                @if ($order->customer_deadline)
                    <p class="mt-2 text-[13px] text-slate-500">Due {{ $order->customer_deadline->timezone(config('app.timezone'))->format('j M Y, H:i') }} · {{ $order->customer_deadline->diffForHumans() }}</p>
                @endif
                @if ($order->statusDescription())
                    <p class="mt-2 max-w-2xl text-[13px] leading-5 text-slate-500">{{ $order->statusDescription() }}</p>
                @endif
            </div>
            <button class="ui-btn ui-btn-primary" type="button" @click="tab = '{{ $primary[1] }}'">{{ $primary[0] }}</button>
        </div>

        @if ($readOnly)
            <p class="mt-5 rounded-2xl bg-wait-soft px-4 py-3 text-[13px] text-wait" role="status">This preview can show the order. Uploads, messages, revisions, and payments are blocked so the live records stay as they are.</p>
        @endif

        <ol class="mt-8 grid grid-cols-3 gap-3 sm:grid-cols-6" aria-label="Progress">
            @foreach ($progress as $index => $label)
                <li>
                    <span class="block h-1 rounded-full {{ $index <= $progressAt ? 'bg-navy' : 'bg-slate-200' }}"></span>
                    <span class="mt-2 block text-[13px] {{ $index === $progressAt ? 'font-semibold text-navy' : 'text-slate-400' }}">{{ $label }}</span>
                </li>
            @endforeach
        </ol>

        <div class="mt-8 flex flex-wrap gap-1 border-b border-slate-200/80" role="tablist">
            @foreach ($tabs as $key => $label)
                <button type="button" role="tab" class="border-b-2 px-3 py-2 text-[13px] font-medium" :class="tab === '{{ $key }}' ? 'border-navy text-navy' : 'border-transparent text-slate-400'" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'">{{ $label }}</button>
            @endforeach
        </div>

        <div class="mt-6" x-show="tab === 'overview'" role="tabpanel">
            <dl class="ui-card grid gap-px overflow-hidden sm:grid-cols-2">
                @foreach ([
                    'Service' => $order->serviceLabel(),
                    'Document' => $order->paper_type_option ?: ($order->paperType->title ?? null),
                    'Words' => (int) $order->words > 0 ? number_format((int) $order->words) : null,
                    'Pages' => (float) $order->pages > 0 ? rtrim(rtrim(number_format((float) $order->pages, 1), '0'), '.') : null,
                    'Slides' => (int) $order->slides > 0 ? (string) (int) $order->slides : null,
                    'Charts' => (int) $order->charts > 0 ? (string) (int) $order->charts : null,
                    'Spacing' => (float) $order->pages > 0 ? ucfirst((string) $order->spacing) : null,
                    'Sources' => (int) $order->sources > 0 ? (string) (int) $order->sources : null,
                    'Writer category' => $order->writerCategoryLabel(),
                    'English type' => $order->englishType->title ?? null,
                    'Target audience' => $order->targetAudience->title ?? null,
                    'Writing tone' => $order->writingTone->title ?? null,
                    'Writing style' => $order->writingStyle->title ?? null,
                    'Content feel' => $order->contentFeel->title ?? null,
                    'Keywords' => $order->keywords ?: null,
                    'Software' => $order->software ?: null,
                    'Price' => '$'.number_format((float) $order->total, 2),
                ] as $label => $value)
                    @if ($value)
                        <div class="bg-white px-5 py-4">
                            <dt class="text-[13px] text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 text-[15px] font-medium">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            @if ($order->showsAcademicRecord())
                <section class="ui-card mt-6 p-6" aria-labelledby="legacy-heading">
                    <h3 id="legacy-heading" class="font-display text-xl tracking-tight text-navy">Recorded on the previous form</h3>
                    <p class="mt-2 text-[13px] text-slate-500">This order already stores academic fields. They are shown here and are not collected on a new order.</p>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        @if ((int) $order->academic_level_id > 0)
                            <div><dt class="text-[13px] text-slate-500">Academic level</dt><dd class="mt-1 font-medium">{{ $order->academicLevel->title ?? 'Level '.$order->academic_level_id }}</dd></div>
                        @endif
                        @if (($order->paper_format_option && $order->paper_format_option !== \App\Support\OrderDefaults::PAPER_FORMAT_OPTION) || ((int) $order->paper_format_id > 0 && (int) $order->paper_format_id !== \App\Support\OrderDefaults::PAPER_FORMAT_ID))
                            <div><dt class="text-[13px] text-slate-500">Citation format</dt><dd class="mt-1 font-medium">{{ $order->paperFormat->title ?? ($order->paper_format_option ?: 'Format '.$order->paper_format_id) }}</dd></div>
                        @endif
                        @if ((int) $order->topic_category_id > 0 && (int) $order->topic_category_id !== 52)
                            <div><dt class="text-[13px] text-slate-500">Subject</dt><dd class="mt-1 font-medium">{{ $order->topic_category_option ?: ($order->discipline->title ?? 'Subject '.$order->topic_category_id) }}</dd></div>
                        @endif
                    </dl>
                </section>
            @endif

            <section class="mt-8">
                <h3 class="font-display text-xl tracking-tight text-navy">Instructions</h3>
                <p class="mt-3 max-w-3xl whitespace-pre-wrap leading-7 text-slate-700">{{ $order->paper_details ?: 'No instructions were stored.' }}</p>
            </section>
        </div>

        <div class="mt-6" x-show="tab === 'files'" x-cloak role="tabpanel">
            <form class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center" method="POST" action="{{ route('desk.files.store', $order) }}" enctype="multipart/form-data" x-data="{ over: false }" :class="over && 'border-navy bg-slate-50'" @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="over = false; const dt = new DataTransfer(); [...$event.dataTransfer.files].forEach(f => dt.items.add(f)); $refs.upload.files = dt.files">
                @csrf
                <x-glyph name="upload" class="mx-auto size-6 text-slate-400" />
                <label class="mt-2 block text-[15px]" for="upload">Drop a file here, or choose one</label>
                <input id="upload" x-ref="upload" name="upload" type="file" class="mt-3 text-[13px]">
                <input name="description" placeholder="What is this file?" class="ui-input mt-3">
                <button class="ui-btn ui-btn-primary mt-3" type="submit">Upload</button>
            </form>
            @foreach (['Your files' => $customerFiles, 'Writer files' => $showWriterFiles ? $writerFiles : collect()] as $heading => $files)
                <h3 class="mt-8 font-display text-xl tracking-tight">{{ $heading }}</h3>
                @if ($heading === 'Writer files' && ! $showWriterFiles)
                    <p class="mt-2 text-[13px] text-slate-500">Writer files appear here once the order is delivered.</p>
                @endif
                <ul class="ui-card mt-3 divide-y divide-slate-100">
                    @forelse ($files as $file)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <x-glyph name="file" class="size-5 text-slate-400" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[15px] font-medium">{{ $file->name }}</span>
                                <span class="text-[13px] text-slate-500">{{ number_format(max(1, (int) $file->size / 1024), 0) }} KB · {{ (int) $file->uploader_id === (int) $order->customer_id ? 'You' : 'Writer' }}</span>
                            </span>
                            <a class="text-[13px] font-medium text-navy" href="{{ route('desk.files.download', [$order, $file]) }}">Download</a>
                        </li>
                    @empty
                        @if ($heading !== 'Writer files' || $showWriterFiles)
                            <li class="px-5 py-8 text-[13px] text-slate-500">No files yet.</li>
                        @endif
                    @endforelse
                </ul>
            @endforeach
        </div>

        <div class="mt-6 flex min-h-[420px] flex-col" x-show="tab === 'messages'" x-cloak role="tabpanel">
            <div class="flex-1 space-y-3">
                @forelse ($order->messages->where('is_deleted', false) as $message)
                    @php $mine = (int) $message->from_department === Departments::CUSTOMER; @endphp
                    <div class="flex gap-3 {{ $mine ? 'flex-row-reverse' : '' }}">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full {{ $mine ? 'bg-navy text-white' : 'bg-slate-100 text-slate-600' }} text-[13px] font-semibold">{{ strtoupper(substr(Departments::NAMES[(int) $message->from_department] ?? 'N', 0, 1)) }}</span>
                        <div class="{{ $mine ? 'chat-mine' : 'chat-theirs' }}">
                            <p class="mb-1 text-[11px] font-medium {{ $mine ? 'text-white/60' : 'text-slate-400' }}">{{ Departments::NAMES[(int) $message->from_department] ?? 'Note' }} · {{ $message->date_add ? \Illuminate\Support\Carbon::parse($message->date_add)->diffForHumans() : '' }}</p>
                            <p class="whitespace-pre-wrap">{{ $message->body }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-[13px] text-slate-500">No messages yet. Write to support, or to the writer once one is assigned.</p>
                @endforelse
            </div>
            <form class="sticky bottom-0 mt-4 space-y-3 bg-app pt-3" method="POST" action="{{ route('desk.messages.store', $order) }}">
                @csrf
                <label class="ui-label" for="to">Send to</label>
                <select id="to" name="to" class="ui-input">
                    <option value="support">Support</option>
                    <option value="writer" @disabled(! $order->writer_id)>The writer</option>
                </select>
                <label class="sr-only" for="body">Message</label>
                <textarea id="body" name="body" required rows="3" class="ui-input h-auto py-3" placeholder="Write the message"></textarea>
                <button class="ui-btn ui-btn-primary" type="submit">Send</button>
            </form>
        </div>

        <div class="mt-6" x-show="tab === 'bids'" x-cloak role="tabpanel">
            <p class="text-[13px] text-slate-500">
                @if ((int) $order->status_id === Order::ORDER_PUBLISHED)
                    This order is open for writers. Bids show up here. A coordinator assigns the writer.
                @elseif ($order->writer_id)
                    Assigned writer: {{ $order->writer?->getFilamentName() }}. Earlier bids stay on the order.
                @else
                    Bids appear when the status is Looking for writer.
                @endif
            </p>
            <ul class="ui-card mt-4 divide-y divide-slate-100">
                @forelse ($bids as $bid)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <span>
                            <span class="block font-medium">{{ $bid->writer?->getFilamentName() ?? 'Writer #'.$bid->writer_id }}</span>
                            <span class="text-[13px] text-slate-500">{{ (int) $bid->bid_time }} hours</span>
                        </span>
                        <span class="tabular-nums">${{ number_format((float) $bid->amount, 2) }}</span>
                        @if ($bid->is_assigned)<span class="pill pill-good">Assigned</span>@endif
                    </li>
                @empty
                    <li class="px-5 py-10 text-[13px] text-slate-500">No bids yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="mt-6" x-show="tab === 'revision'" x-cloak role="tabpanel">
            @if ($order->revisions->isNotEmpty())
                <ul class="ui-card mb-4 divide-y divide-slate-100">
                    @foreach ($order->revisions as $revision)
                        <li class="px-5 py-4">
                            <p class="font-medium">{{ $revision->free_revision ? 'Free revision' : 'Revision' }} · {{ (int) $revision->client_deadline }} hours</p>
                            <p class="mt-1 whitespace-pre-wrap text-[13px] text-slate-500">{{ $revision->instructions }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if (in_array((int) $order->status_id, [Order::DELIVERED, Order::FINISHED], true))
                <form class="ui-card space-y-3 p-6" method="POST" action="{{ route('desk.revisions.store', $order) }}" x-data="{ ask: false }" @submit.prevent="ask = true">
                    @csrf
                    <label class="ui-label" for="instructions">What should change?</label>
                    <textarea id="instructions" name="instructions" required minlength="10" rows="4" class="ui-input h-auto py-3"></textarea>
                    <label class="ui-label" for="hours">Needed within</label>
                    <select id="hours" name="hours" class="ui-input">
                        @foreach ([3, 6, 12, 24, 48, 72] as $hours)
                            <option value="{{ $hours }}">{{ $hours }} hours</option>
                        @endforeach
                    </select>
                    <button class="ui-btn ui-btn-primary" type="button" @click="ask = true">Request a revision</button>
                    <div class="fixed inset-0 z-50 grid place-items-center bg-navy/40 p-4" x-show="ask" x-cloak>
                        <div class="ui-card w-full max-w-md p-6" role="dialog" aria-modal="true" aria-labelledby="confirm-revision">
                            <h3 id="confirm-revision" class="font-display text-xl tracking-tight">Send this revision request?</h3>
                            <p class="mt-2 text-[13px] text-slate-500">The order moves to Revision. You can still message support.</p>
                            <div class="mt-5 flex justify-end gap-2">
                                <button class="ui-btn ui-btn-quiet" type="button" @click="ask = false">Cancel</button>
                                <button class="ui-btn ui-btn-primary" type="submit">Confirm</button>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <p class="text-[13px] text-slate-500">A revision can be requested once the work has been delivered.</p>
            @endif
        </div>

        <div class="mt-6" x-show="tab === 'payment'" x-cloak role="tabpanel" x-data="paymentDesk(@js($payPhase))" data-payment>
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div>
                    <div x-show="phase === 'waiting'" x-cloak class="ui-card p-8 text-center">
                        <div class="mx-auto size-8 animate-spin rounded-full border-2 border-slate-200 border-t-navy" aria-hidden="true"></div>
                        <h3 class="mt-4 font-display text-xl tracking-tight">Waiting for the M-Pesa prompt</h3>
                        <p class="mt-2 text-[15px] text-slate-600">Check the phone. Enter your M-Pesa PIN when the prompt appears. This page updates when the payment is confirmed.</p>
                        @if (session('status'))<p class="mt-3 text-[13px] text-slate-500">{{ session('status') }}</p>@endif
                    </div>
                    <div x-show="phase === 'success'" x-cloak class="ui-card p-8">
                        <h3 class="font-display text-xl tracking-tight text-good">Payment received</h3>
                        <p class="mt-2 text-[15px] text-slate-600">{{ session('status') }}</p>
                    </div>
                    <div x-show="phase === 'failed'" x-cloak class="ui-card p-8">
                        <h3 class="font-display text-xl tracking-tight text-bad">Payment was not started</h3>
                        <p class="mt-2 text-[15px] text-slate-600">{{ session('status') }}</p>
                        <button class="ui-btn ui-btn-quiet mt-4" type="button" @click="phase = 'choose'">Try another method</button>
                    </div>
                    <form class="space-y-3" method="POST" action="{{ route('desk.pay', $order) }}" x-show="phase === 'choose'" @submit="send($event)">
                        @csrf
                        @foreach ([
                            'mpesa' => ['M-Pesa', 'A prompt on a Safaricom phone.', 'phone'],
                            'stripe' => ['Card', 'Visa, Mastercard, and other cards via Stripe.', 'card'],
                            'paypal' => ['PayPal', 'Pay with a PayPal balance or card.', 'wallet'],
                            'pesapal' => ['Pesapal', 'Card and mobile money via Pesapal.', 'card'],
                            'wallet' => ['Wallet', 'Pay from the balance on this account.', 'wallet'],
                        ] as $value => $meta)
                            <label class="ui-card flex cursor-pointer items-center gap-4 p-4" :class="provider === '{{ $value }}' ? 'ring-2 ring-navy' : ''">
                                <input type="radio" name="provider" value="{{ $value }}" x-model="provider" @checked($value === 'mpesa')>
                                <x-glyph :name="$meta[2]" class="size-5 text-slate-500" />
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium">{{ $meta[0] }}</span>
                                    <span class="block text-[13px] text-slate-500">{{ $meta[1] }}</span>
                                </span>
                            </label>
                        @endforeach
                        <div x-show="provider === 'mpesa'" class="pt-2">
                            <label class="ui-label" for="phone">M-Pesa phone</label>
                            <input id="phone" name="phone" class="ui-input" placeholder="07…" inputmode="tel" autocomplete="tel">
                            <p class="ui-hint">Use a Safaricom number, 07… or 2547…</p>
                        </div>
                        @if ($safeMode)
                            <p class="text-[13px] text-slate-500">Safe mode is on. M-Pesa, PayPal, Stripe, and Pesapal will not be contacted.</p>
                        @endif
                        <button class="ui-btn ui-btn-primary" type="submit" @if ($due <= 0) disabled @endif>{{ $due <= 0 ? 'Nothing due' : 'Pay $'.number_format($due, 2) }}</button>
                    </form>
                </div>
                <aside class="ui-card h-fit p-6">
                    <p class="text-[13px] text-slate-500">Amount due</p>
                    <p class="mt-1 font-display text-[32px] tabular-nums tracking-tight">${{ number_format($due, 2) }}</p>
                    <p class="mt-2 text-[13px] text-slate-500">Order total ${{ number_format((float) $order->total, 2) }}. Wallet ${{ number_format((float) $customer->wallet_balance, 2) }}.</p>
                    <form class="mt-6 space-y-3 border-t border-slate-100 pt-4" method="POST" action="{{ route('desk.rating.store', $order) }}">
                        @csrf
                        <p class="text-[13px] font-medium">After delivery</p>
                        <label class="ui-label" for="service_rating">Service, 1 to 5</label>
                        <input id="service_rating" name="service_rating" type="number" min="1" max="5" required class="ui-input">
                        <label class="ui-label" for="paper_rating">Work, 1 to 5</label>
                        <input id="paper_rating" name="paper_rating" type="number" min="1" max="5" required class="ui-input">
                        <textarea name="comment" rows="3" class="ui-input h-auto py-3" placeholder="Optional note"></textarea>
                        <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="paper_on_time" value="1"> Delivered on time</label>
                        <button class="ui-btn ui-btn-quiet w-full" type="submit">Save rating</button>
                    </form>
                    <form class="mt-3 flex gap-2" method="POST" action="{{ route('desk.tip.store', $order) }}">
                        @csrf
                        <label class="sr-only" for="amount">Tip amount</label>
                        <input id="amount" name="amount" type="number" min="1" step="1" value="5" class="ui-input w-24">
                        <button class="ui-btn ui-btn-quiet" type="submit">Tip</button>
                    </form>
                </aside>
            </div>
        </div>
    </div>
@endsection
