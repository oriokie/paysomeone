@extends('layouts.desk')
@section('title', 'Order #'.$order->order_id.' — '.$siteName)

@section('content')
    @php
        $due = max(0, (float) $order->total - (float) $order->total_paid);
        $events = array_values(array_filter([
            $order->date_add ? ['Placed', $order->date_add] : null,
            $order->paid_at ? ['Paid', $order->paid_at] : null,
            $order->writer_assigned_at ? ['Writer assigned', $order->writer_assigned_at] : null,
            $order->delivered_at ? ['Delivered', $order->delivered_at] : null,
            $order->finished_at ? ['Finished', $order->finished_at] : null,
        ]));
    @endphp
    <p class="text-sm text-muted">Order #{{ $order->order_id }}</p>
    <div class="mt-2 flex flex-wrap items-center gap-3">
        <h1 class="font-display text-4xl text-navy">{{ $order->title }}</h1>
        <x-pill :status="$order->status_id" :label="$order->statusLabel()" />
    </div>
    <p class="mt-3 text-sm text-muted">{{ $order->is_paid ? 'Paid' : 'Not paid' }} · ${{ number_format((float) $order->total, 2) }}</p>

    @if ($readOnly)
        <p class="mt-5 rounded-2xl border border-wait/30 bg-wait-soft px-4 py-3 text-sm text-wait" role="status">This preview can show the order. Uploads, messages, revisions, and payments are blocked so the live records stay as they are.</p>
    @endif

    <div class="mt-8 grid gap-8 lg:grid-cols-[1.3fr_0.7fr]">
        <div class="space-y-8">
            @if ($events)
                <ol class="card p-5" aria-label="Order timeline">
                    @foreach ($events as $event)
                        <li class="relative border-l border-line py-2 pl-5">
                            <span class="absolute top-3 -left-1 size-2 rounded-full bg-navy" aria-hidden="true"></span>
                            <p class="text-sm font-semibold">{{ $event[0] }}</p>
                            <p class="text-xs text-muted">{{ \Illuminate\Support\Carbon::parse($event[1])->timezone(config('app.timezone'))->format('j M Y, H:i') }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif

            <section>
                <h2 class="font-display text-2xl text-navy">Brief</h2>
                <p class="mt-3 whitespace-pre-wrap leading-7 text-ink/85">{{ $order->paper_details }}</p>
            </section>

            <section aria-labelledby="files-heading">
                <h2 id="files-heading" class="font-display text-2xl text-navy">Files</h2>
                <ul class="card mt-3 divide-y divide-line">
                    @forelse ($order->files as $file)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span class="font-medium">{{ $file->name }}</span>
                            <a class="font-semibold text-accent" href="{{ route('desk.files.download', [$order, $file]) }}">Download</a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-muted">No files yet. Add the brief, a draft, or a reference.</li>
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

            <section aria-labelledby="messages-heading">
                <h2 id="messages-heading" class="font-display text-2xl text-navy">Messages</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($order->messages->where('is_deleted', false) as $message)
                        @php $mine = (int) $message->from_department === \App\Support\Departments::CUSTOMER; @endphp
                        <div class="{{ $mine ? 'chat-mine' : 'chat-theirs' }}">
                            <p class="mb-1 text-[11px] font-semibold tracking-wide uppercase {{ $mine ? 'text-white/60' : 'text-muted' }}">{{ \App\Support\Departments::NAMES[(int) $message->from_department] ?? 'Note' }}</p>
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
            </section>

            <section aria-labelledby="revision-heading">
                <h2 id="revision-heading" class="font-display text-2xl text-navy">Revision</h2>
                <form class="card mt-3 space-y-3 p-5" method="POST" action="{{ route('desk.revisions.store', $order) }}">
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
            </section>
        </div>

        <aside class="space-y-6">
            <section class="quote-card rounded-3xl p-6 text-white" aria-labelledby="pay-heading">
                <h2 id="pay-heading" class="text-xs font-semibold tracking-[0.16em] text-accent-bright uppercase">Payment</h2>
                <p class="mt-3 font-display text-5xl">${{ number_format($due, 2) }}</p>
                <p class="mt-1 text-sm text-white/60">{{ $due <= 0 ? 'Nothing due. This order is paid.' : 'due of $'.number_format((float) $order->total, 2) }}</p>
                @if ($safeMode)
                    <p class="mt-4 text-sm text-accent-bright">Safe mode is on. M-Pesa, PayPal, Stripe, and Pesapal will not be contacted.</p>
                @endif
                <form class="mt-5 space-y-3" method="POST" action="{{ route('desk.pay', $order) }}" @if ($due <= 0) hidden @endif>
                    @csrf
                    <button class="btn btn-invert w-full" name="provider" value="wallet" type="submit">Pay from wallet (${{ number_format((float) $customer->wallet_balance, 2) }})</button>
                    <label class="block text-xs text-white/70" for="phone">M-Pesa phone</label>
                    <input id="phone" name="phone" class="field text-ink" placeholder="07…">
                    <button class="btn btn-on-dark w-full" name="provider" value="mpesa" type="submit">M-Pesa</button>
                    <button class="btn btn-on-dark w-full" name="provider" value="paypal" type="submit">PayPal</button>
                    <button class="btn btn-on-dark w-full" name="provider" value="stripe" type="submit">Stripe</button>
                    <button class="btn btn-on-dark w-full" name="provider" value="pesapal" type="submit">Pesapal</button>
                </form>
            </section>

            <section class="card p-6" aria-labelledby="rate-heading">
                <h2 id="rate-heading" class="font-display text-xl text-navy">Rating</h2>
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
        </aside>
    </div>
@endsection
