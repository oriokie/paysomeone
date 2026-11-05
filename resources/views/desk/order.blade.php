@extends('layouts.desk')
@section('title', 'Order #'.$order->order_id.' — '.$siteName)

@section('content')
    @php
        use App\Models\Order;
        use App\Support\Departments;
        use App\Support\OrderStatuses;

        $due = max(0, (float) $order->total - (float) $order->total_paid);
        $tab = session('tab', request('tab', 'details'));
        $tabs = ['details' => 'Details', 'files' => 'Files', 'messages' => 'Messages', 'bids' => 'Bids', 'revision' => 'Revision', 'payment' => 'Payment'];
        $customerFiles = $order->files->filter(fn ($file) => (int) $file->uploader_id === (int) $order->customer_id);
        $writerFiles = $order->files->filter(fn ($file) => (int) $file->uploader_id !== (int) $order->customer_id);
        $showWriterFiles = in_array((int) $order->status_id, [Order::DELIVERED, Order::FINISHED], true);
        $bids = $order->bids->where('is_deleted', false);
        $flowIndex = array_keys(OrderStatuses::FLOW);
        $currentFlow = array_search((int) $order->status_id, $flowIndex, true);
    @endphp

    <p class="text-sm text-muted">Order #{{ $order->order_id }} · {{ $order->serviceLabel() }}</p>
    <div class="mt-2 flex flex-wrap items-center gap-3">
        <h1 class="font-display text-4xl text-navy">{{ $order->title }}</h1>
        <x-pill :status="$order->status_id" :label="$order->statusLabel()" />
    </div>
    @if ($order->statusDescription())
        <p class="mt-3 max-w-3xl text-sm leading-6 text-muted">{{ $order->statusDescription() }}</p>
    @endif

    @if ($readOnly)
        <p class="mt-5 rounded-2xl border border-wait/30 bg-wait-soft px-4 py-3 text-sm text-wait" role="status">This preview can show the order. Uploads, messages, revisions, and payments are blocked so the live records stay as they are.</p>
    @endif

    <ol class="mt-6 flex gap-2 overflow-x-auto pb-1 text-xs" aria-label="Order lifecycle">
        @foreach (OrderStatuses::FLOW as $id => $label)
            @php $index = array_search($id, $flowIndex, true); @endphp
            <li class="shrink-0 rounded-full px-3 py-1 {{ $id === (int) $order->status_id ? 'bg-navy text-white' : ($currentFlow !== false && $index < $currentFlow ? 'bg-good-soft text-good' : 'bg-paper text-muted') }}">{{ $label }}</li>
        @endforeach
    </ol>

    <div class="mt-8" x-data="{ tab: @js($tab) }">
        <div class="flex flex-wrap gap-1 border-b border-line" role="tablist">
            @foreach ($tabs as $key => $label)
                <button type="button" role="tab" class="shrink-0 border-b-2 px-3 py-2 text-sm font-medium" :class="tab === '{{ $key }}' ? 'border-navy text-navy' : 'border-transparent text-muted'" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'">{{ $label }}</button>
            @endforeach
        </div>

        <div class="mt-6" x-show="tab === 'details'" role="tabpanel">
            <dl class="card divide-y divide-line">
                @foreach ([
                    'Service' => $order->serviceLabel(),
                    'Document' => $order->paper_type_option ?: ($order->paperType->title ?? '—'),
                    'Words' => (int) $order->words > 0 ? number_format((int) $order->words) : null,
                    'Pages' => (float) $order->pages > 0 ? rtrim(rtrim(number_format((float) $order->pages, 1), '0'), '.') : null,
                    'Slides' => (int) $order->slides > 0 ? (string) (int) $order->slides : null,
                    'Charts' => (int) $order->charts > 0 ? (string) (int) $order->charts : null,
                    'Spacing' => (float) $order->pages > 0 ? ucfirst((string) $order->spacing) : null,
                    'Sources' => (int) $order->sources > 0 ? (string) (int) $order->sources : null,
                    'Deadline' => $order->customer_deadline ? \Illuminate\Support\Carbon::parse($order->customer_deadline)->timezone(config('app.timezone'))->format('j M Y, H:i') : null,
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
                        <div class="grid gap-1 px-5 py-3 sm:grid-cols-[12rem_1fr]">
                            <dt class="text-sm text-muted">{{ $label }}</dt>
                            <dd class="text-sm font-medium">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            @if ($order->showsAcademicRecord())
                <section class="card mt-6 p-5" aria-labelledby="legacy-heading">
                    <h2 id="legacy-heading" class="font-display text-xl text-navy">Recorded on the previous form</h2>
                    <p class="mt-2 text-sm text-muted">This order already stores academic fields. They are shown here and are not collected on a new order.</p>
                    <dl class="mt-4 divide-y divide-line">
                        @if ((int) $order->academic_level_id > 0)
                            <div class="grid gap-1 py-3 sm:grid-cols-[12rem_1fr]"><dt class="text-sm text-muted">Academic level</dt><dd class="text-sm font-medium">{{ $order->academicLevel->title ?? 'Level '.$order->academic_level_id }}</dd></div>
                        @endif
                        @if ((int) $order->paper_format_id > 0 && (int) $order->paper_format_id !== 4)
                            <div class="grid gap-1 py-3 sm:grid-cols-[12rem_1fr]"><dt class="text-sm text-muted">Citation format</dt><dd class="text-sm font-medium">{{ $order->paperFormat->title ?? ($order->paper_format_option ?: 'Format '.$order->paper_format_id) }}</dd></div>
                        @endif
                        @if ((int) $order->topic_category_id > 0 && (int) $order->topic_category_id !== 52)
                            <div class="grid gap-1 py-3 sm:grid-cols-[12rem_1fr]"><dt class="text-sm text-muted">Subject</dt><dd class="text-sm font-medium">{{ $order->topic_category_option ?: ($order->discipline->title ?? 'Subject '.$order->topic_category_id) }}</dd></div>
                        @endif
                    </dl>
                </section>
            @endif

            <section class="mt-8">
                <h2 class="font-display text-2xl text-navy">Instructions</h2>
                <p class="mt-3 whitespace-pre-wrap leading-7 text-ink/85">{{ $order->paper_details ?: 'No instructions were stored.' }}</p>
            </section>

            @if ($order->writer_id)
                <p class="mt-6 text-sm">Assigned writer: <span class="font-medium">{{ $order->writer?->getFilamentName() }}</span></p>
            @endif
        </div>

        <div class="mt-6 space-y-8" x-show="tab === 'files'" x-cloak role="tabpanel">
            <section>
                <h2 class="font-display text-2xl text-navy">Your files</h2>
                <ul class="card mt-3 divide-y divide-line">
                    @forelse ($customerFiles as $file)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span>
                                <span class="block font-medium">{{ $file->name }}</span>
                                @if ($file->file_description)<span class="text-muted">{{ $file->file_description }}</span>@endif
                            </span>
                            <a class="font-semibold text-accent" href="{{ route('desk.files.download', [$order, $file]) }}">Download</a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-muted">No files from you yet.</li>
                    @endforelse
                </ul>
                <form class="mt-4 space-y-3" method="POST" action="{{ route('desk.files.store', $order) }}" enctype="multipart/form-data" x-data="{ over: false }">
                    @csrf
                    <div class="dropzone" :class="over && 'is-over'" @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="over = false; const dt = new DataTransfer(); [...$event.dataTransfer.files].forEach(f => dt.items.add(f)); $refs.upload.files = dt.files">
                        <label class="label" for="upload">Drop a file here, or choose one</label>
                        <input id="upload" x-ref="upload" name="upload" type="file" class="mt-3 text-sm">
                    </div>
                    <input name="description" placeholder="What is this file?" class="field">
                    <button class="btn btn-primary" type="submit">Upload</button>
                </form>
            </section>
            <section>
                <h2 class="font-display text-2xl text-navy">Writer files</h2>
                @if (! $showWriterFiles)
                    <p class="mt-3 text-sm text-muted">Writer files appear here once the order is delivered.</p>
                @else
                    <ul class="card mt-3 divide-y divide-line">
                        @forelse ($writerFiles as $file)
                            <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span class="font-medium">{{ $file->name }}</span>
                                <a class="font-semibold text-accent" href="{{ route('desk.files.download', [$order, $file]) }}">Download</a>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-sm text-muted">No writer files yet.</li>
                        @endforelse
                    </ul>
                @endif
            </section>
        </div>

        <div class="mt-6" x-show="tab === 'messages'" x-cloak role="tabpanel">
            <div class="space-y-3">
                @forelse ($order->messages->where('is_deleted', false) as $message)
                    @php $mine = (int) $message->from_department === Departments::CUSTOMER; @endphp
                    <div class="{{ $mine ? 'chat-mine' : 'chat-theirs' }}">
                        <p class="mb-1 text-[11px] font-semibold tracking-wide uppercase {{ $mine ? 'text-white/60' : 'text-muted' }}">{{ Departments::NAMES[(int) $message->from_department] ?? 'Note' }}</p>
                        <p class="whitespace-pre-wrap">{{ $message->body }}</p>
                    </div>
                @empty
                    <p class="text-sm text-muted">No messages yet. Write to support, or to the writer once one is assigned.</p>
                @endforelse
            </div>
            <form class="mt-4 space-y-3" method="POST" action="{{ route('desk.messages.store', $order) }}">
                @csrf
                <label class="label" for="to">Send to</label>
                <select id="to" name="to" class="field">
                    <option value="support">Support</option>
                    <option value="writer" @disabled(! $order->writer_id)>The writer</option>
                </select>
                <label class="sr-only" for="body">Message</label>
                <textarea id="body" name="body" required rows="4" class="field" placeholder="Write the message"></textarea>
                <button class="btn btn-primary" type="submit">Send</button>
            </form>
        </div>

        <div class="mt-6" x-show="tab === 'bids'" x-cloak role="tabpanel">
            <p class="text-sm text-muted">
                @if ((int) $order->status_id === Order::ORDER_PUBLISHED)
                    This order is open for writers. Bids show up here. A coordinator assigns the writer.
                @elseif ($order->writer_id)
                    A writer has been assigned. Earlier bids stay on the order.
                @else
                    Bids appear when the order is looking for a writer.
                @endif
            </p>
            <ul class="card mt-4 divide-y divide-line">
                @forelse ($bids as $bid)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm">
                        <span>
                            <span class="block font-medium">{{ $bid->writer?->getFilamentName() ?? 'Writer #'.$bid->writer_id }}</span>
                            <span class="text-muted">{{ (int) $bid->bid_time }} hours</span>
                        </span>
                        <span class="font-medium">${{ number_format((float) $bid->amount, 2) }}</span>
                        @if ($bid->is_assigned)
                            <span class="pill pill-good">Assigned</span>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-sm text-muted">No bids yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="mt-6" x-show="tab === 'revision'" x-cloak role="tabpanel">
            @if ($order->revisions->isNotEmpty())
                <ul class="card mb-4 divide-y divide-line">
                    @foreach ($order->revisions as $revision)
                        <li class="px-5 py-4 text-sm">
                            <p class="font-medium">{{ $revision->free_revision ? 'Free revision' : 'Revision' }} · {{ (int) $revision->client_deadline }} hours</p>
                            <p class="mt-1 whitespace-pre-wrap text-muted">{{ $revision->instructions }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if (in_array((int) $order->status_id, [Order::DELIVERED, Order::FINISHED], true))
                <form class="card space-y-3 p-5" method="POST" action="{{ route('desk.revisions.store', $order) }}">
                    @csrf
                    <label class="label" for="instructions">What should change?</label>
                    <textarea id="instructions" name="instructions" required minlength="10" rows="4" class="field"></textarea>
                    <label class="label" for="hours">Needed within</label>
                    <select id="hours" name="hours" class="field">
                        @foreach ([3, 6, 12, 24, 48, 72] as $hours)
                            <option value="{{ $hours }}">{{ $hours }} hours</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary" type="submit">Request a revision</button>
                </form>
            @else
                <p class="text-sm text-muted">A revision can be requested once the work has been delivered.</p>
            @endif
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_18rem]" x-show="tab === 'payment'" x-cloak role="tabpanel">
            <section class="quote-card rounded-3xl p-6 text-white" aria-labelledby="pay-heading">
                <h2 id="pay-heading" class="text-xs font-semibold tracking-[0.16em] text-accent-bright uppercase">Payment</h2>
                <p class="mt-3 font-display text-5xl">${{ number_format($due, 2) }}</p>
                <p class="mt-1 text-sm text-white/60">{{ $due <= 0 ? 'Nothing due. This order is paid.' : 'due of $'.number_format((float) $order->total, 2) }}</p>
                <p class="mt-4 text-sm text-white/70">Wallet balance ${{ number_format((float) $customer->wallet_balance, 2) }}. Card and mobile money open a checkout. Nothing is charged until that checkout confirms.</p>
                @if ($safeMode)
                    <p class="mt-4 text-sm text-accent-bright">Safe mode is on. M-Pesa, PayPal, Stripe, and Pesapal will not be contacted.</p>
                @endif
                <form class="mt-5 space-y-3" method="POST" action="{{ route('desk.pay', $order) }}" @if ($due <= 0) hidden @endif>
                    @csrf
                    <button class="btn btn-invert w-full" name="provider" value="wallet" type="submit">Pay from wallet</button>
                    <label class="block text-xs text-white/70" for="phone">M-Pesa phone</label>
                    <input id="phone" name="phone" class="field text-ink" placeholder="07…" inputmode="tel" autocomplete="tel">
                    <button class="btn btn-on-dark w-full" name="provider" value="mpesa" type="submit">M-Pesa</button>
                    <button class="btn btn-on-dark w-full" name="provider" value="paypal" type="submit">PayPal</button>
                    <button class="btn btn-on-dark w-full" name="provider" value="stripe" type="submit">Stripe</button>
                    <button class="btn btn-on-dark w-full" name="provider" value="pesapal" type="submit">Pesapal</button>
                </form>
            </section>
            <section class="card p-6" aria-labelledby="rate-heading">
                <h2 id="rate-heading" class="font-display text-xl text-navy">After delivery</h2>
                <form class="mt-3 space-y-2 text-sm" method="POST" action="{{ route('desk.rating.store', $order) }}">
                    @csrf
                    <label class="label" for="service_rating">Service, 1 to 5</label>
                    <input id="service_rating" name="service_rating" type="number" min="1" max="5" required class="field">
                    <label class="label" for="paper_rating">Work, 1 to 5</label>
                    <input id="paper_rating" name="paper_rating" type="number" min="1" max="5" required class="field">
                    <textarea name="comment" rows="3" class="field" placeholder="Optional note"></textarea>
                    <label class="flex items-center gap-2"><input type="checkbox" name="paper_on_time" value="1"> Delivered on time</label>
                    <button class="btn btn-primary" type="submit">Save rating</button>
                </form>
                <form class="mt-4 flex gap-2" method="POST" action="{{ route('desk.tip.store', $order) }}">
                    @csrf
                    <label class="sr-only" for="amount">Tip amount</label>
                    <input id="amount" name="amount" type="number" min="1" step="1" value="5" class="field mt-0 w-24">
                    <button class="btn btn-secondary" type="submit">Tip from wallet</button>
                </form>
            </section>
        </div>
    </div>
@endsection
