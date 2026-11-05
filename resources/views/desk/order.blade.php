@extends('layouts.site')
@section('title', 'Order #'.$order->order_id.' — '.$siteName)

@section('content')
    @include('desk.nav')
    <section class="mx-auto grid max-w-6xl gap-8 px-5 py-12 lg:grid-cols-[1.3fr_0.7fr]">
        <div class="space-y-8">
            <div>
                <p class="text-sm text-ink/50">Order #{{ $order->order_id }}</p>
                <h1 class="mt-2 font-display text-4xl text-navy">{{ $order->title }}</h1>
                <p class="mt-3 text-sm">{{ $order->statusLabel() }} · {{ $order->is_paid ? 'Paid' : 'Not paid' }} · ${{ number_format((float) $order->total, 2) }}</p>
                <p class="mt-4 whitespace-pre-wrap leading-7 text-ink/80">{{ $order->paper_details }}</p>
            </div>

            @if ($readOnly)
                <p class="rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm" role="status">This preview can show the order. Uploads, messages, revisions, and payments are blocked so the live records stay as they are.</p>
            @endif

            <section aria-labelledby="files-heading">
                <h2 id="files-heading" class="font-display text-2xl text-navy">Files</h2>
                <ul class="mt-3 divide-y divide-line rounded-3xl border border-line bg-white">
                    @forelse ($order->files as $file)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span>{{ $file->name }} <span class="text-ink/50">{{ $file->source }}</span></span>
                            <a class="underline" href="{{ route('desk.files.download', [$order, $file]) }}">Download</a>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-ink/60">No files yet.</li>
                    @endforelse
                </ul>
                <form class="mt-4 space-y-3" method="POST" action="{{ route('desk.files.store', $order) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="block text-sm font-medium" for="upload">Add a file</label>
                    <input id="upload" name="upload" type="file" class="text-sm">
                    <input name="description" placeholder="What is this file?" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
                    <button class="rounded-full bg-navy px-4 py-2 text-sm text-white" type="submit">Upload</button>
                </form>
            </section>

            <section aria-labelledby="messages-heading">
                <h2 id="messages-heading" class="font-display text-2xl text-navy">Messages</h2>
                <ol class="mt-3 space-y-3">
                    @forelse ($order->messages->where('is_deleted', false) as $message)
                        <li class="rounded-3xl bg-white px-5 py-4">
                            <p class="text-xs tracking-wide text-ink/50 uppercase">{{ \App\Support\Departments::NAMES[(int) $message->from_department] ?? 'Note' }} to {{ \App\Support\Departments::NAMES[(int) $message->to_department] ?? 'the desk' }}</p>
                            <p class="mt-2 whitespace-pre-wrap text-sm leading-6">{{ $message->body }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-ink/60">No messages on this order.</li>
                    @endforelse
                </ol>
                <form class="mt-4 space-y-3" method="POST" action="{{ route('desk.messages.store', $order) }}">
                    @csrf
                    <label class="text-sm font-medium" for="to">Send to</label>
                    <select id="to" name="to" class="w-full rounded-xl border border-line bg-white px-3 py-2 text-sm">
                        <option value="support">Support</option>
                        <option value="writer" @disabled(! $order->writer_id)>The writer</option>
                    </select>
                    <label class="sr-only" for="body">Message</label>
                    <textarea id="body" name="body" required rows="4" class="w-full rounded-xl border border-line px-3 py-2 text-sm" placeholder="Write the message"></textarea>
                    <button class="rounded-full bg-navy px-4 py-2 text-sm text-white" type="submit">Send</button>
                </form>
            </section>

            <section aria-labelledby="revision-heading">
                <h2 id="revision-heading" class="font-display text-2xl text-navy">Revision</h2>
                <form class="mt-3 space-y-3" method="POST" action="{{ route('desk.revisions.store', $order) }}">
                    @csrf
                    <label class="text-sm font-medium" for="instructions">What should change?</label>
                    <textarea id="instructions" name="instructions" required minlength="10" rows="4" class="w-full rounded-xl border border-line px-3 py-2 text-sm"></textarea>
                    <label class="text-sm font-medium" for="hours">Needed within</label>
                    <select id="hours" name="hours" class="rounded-xl border border-line bg-white px-3 py-2 text-sm">
                        @foreach ([3, 6, 12, 24, 48, 72] as $hours)
                            <option value="{{ $hours }}">{{ $hours }} hours</option>
                        @endforeach
                    </select>
                    <button class="block rounded-full bg-navy px-4 py-2 text-sm text-white" type="submit">Request a revision</button>
                </form>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-3xl bg-navy p-6 text-white" aria-labelledby="pay-heading">
                <h2 id="pay-heading" class="text-xs tracking-[0.16em] text-[#d7c4a3] uppercase">Payment</h2>
                @php $due = max(0, (float) $order->total - (float) $order->total_paid); @endphp
                <p class="mt-3 font-display text-4xl">${{ number_format($due, 2) }}</p>
                <p class="mt-1 text-sm text-white/60">{{ $due <= 0 ? 'Nothing due. This order is paid.' : 'due of $'.number_format((float) $order->total, 2) }}</p>
                @if ($safeMode)
                    <p class="mt-4 text-sm text-amber-100">Safe mode is on. M-Pesa, PayPal, Stripe, and Pesapal will not be contacted.</p>
                @endif
                <form class="mt-5 space-y-3" method="POST" action="{{ route('desk.pay', $order) }}" @if ($due <= 0) hidden @endif>
                    @csrf
                    <button class="w-full rounded-full bg-white px-4 py-2 text-sm font-semibold text-navy" name="provider" value="wallet" type="submit">Pay from wallet (${{ number_format((float) $customer->wallet_balance, 2) }})</button>
                    <label class="block text-xs text-white/70" for="phone">M-Pesa phone</label>
                    <input id="phone" name="phone" class="w-full rounded-xl px-3 py-2 text-sm text-ink" placeholder="07…">
                    <button class="w-full rounded-full border border-white/30 px-4 py-2 text-sm" name="provider" value="mpesa" type="submit">M-Pesa</button>
                    <button class="w-full rounded-full border border-white/30 px-4 py-2 text-sm" name="provider" value="paypal" type="submit">PayPal</button>
                    <button class="w-full rounded-full border border-white/30 px-4 py-2 text-sm" name="provider" value="stripe" type="submit">Stripe</button>
                    <button class="w-full rounded-full border border-white/30 px-4 py-2 text-sm" name="provider" value="pesapal" type="submit">Pesapal</button>
                </form>
            </section>

            <section class="rounded-3xl bg-white p-6" aria-labelledby="rate-heading">
                <h2 id="rate-heading" class="font-display text-xl text-navy">Rating</h2>
                <form class="mt-3 space-y-2 text-sm" method="POST" action="{{ route('desk.rating.store', $order) }}">
                    @csrf
                    <label class="block" for="service_rating">Service, 1 to 5</label>
                    <input id="service_rating" name="service_rating" type="number" min="1" max="5" required class="w-full rounded-xl border border-line px-3 py-2">
                    <label class="block" for="paper_rating">Work, 1 to 5</label>
                    <input id="paper_rating" name="paper_rating" type="number" min="1" max="5" required class="w-full rounded-xl border border-line px-3 py-2">
                    <textarea name="comment" rows="3" class="w-full rounded-xl border border-line px-3 py-2" placeholder="Optional note"></textarea>
                    <label class="flex items-center gap-2"><input type="checkbox" name="paper_on_time" value="1"> Delivered on time</label>
                    <button class="rounded-full bg-navy px-4 py-2 text-white" type="submit">Save rating</button>
                </form>
                <form class="mt-4 flex gap-2" method="POST" action="{{ route('desk.tip.store', $order) }}">
                    @csrf
                    <label class="sr-only" for="amount">Tip amount</label>
                    <input id="amount" name="amount" type="number" min="1" step="1" value="5" class="w-24 rounded-xl border border-line px-3 py-2 text-sm">
                    <button class="rounded-full border border-line px-4 py-2 text-sm" type="submit">Tip from wallet</button>
                </form>
            </section>
        </aside>
    </section>
@endsection
