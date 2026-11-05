@extends('layouts.office')
@section('title', 'Order #'.$order->order_id.' — '.$siteName)
@section('heading', 'Order #'.$order->order_id)

@section('content')
    @php $can = fn ($key) => \App\Services\Office\Access::allows($staff, $key); @endphp
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-[13px] text-slate-500">{{ $order->serviceLabel() }} · {{ $customer['name'] }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <h2 class="font-display text-[32px] tracking-tight text-navy">{{ $order->title }}</h2>
                <x-pill :status="$order->status_id" :label="$order->statusLabel()" />
            </div>
            @if ($customer['email'])
                <p class="mt-2 text-[13px] text-slate-500">{{ $customer['email'] }} @if($customer['phone']) · {{ $customer['phone'] }} @endif</p>
            @else
                <p class="mt-2 text-[13px] text-slate-500">Customer contact details are hidden for this role.</p>
            @endif
        </div>
    </div>

    <dl class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="ui-card p-4"><dt class="text-[13px] text-slate-500">Customer deadline</dt><dd class="mt-1 font-medium">{{ $order->customer_deadline?->timezone(config('app.timezone'))->format('j M Y, H:i') ?? '—' }}</dd></div>
        <div class="ui-card p-4"><dt class="text-[13px] text-slate-500">Writer deadline</dt><dd class="mt-1 font-medium">{{ $order->writer_deadline?->timezone(config('app.timezone'))->format('j M Y, H:i') ?? '—' }}</dd></div>
        <div class="ui-card p-4"><dt class="text-[13px] text-slate-500">Total</dt><dd class="mt-1 font-display text-2xl tabular-nums">{{ $can('customers.pii') || $can('orders.view_all') ? '$'.number_format((float) $order->total, 2) : 'Hidden' }}</dd></div>
    </dl>

    <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-6">
            <section class="ui-card p-6">
                <h3 class="font-display text-xl tracking-tight">Timeline</h3>
                <ol class="mt-4 space-y-3 text-[13px]">
                    @foreach (array_filter([
                        $order->date_add ? ['Placed', $order->date_add] : null,
                        $order->paid_at ? ['Paid', $order->paid_at] : null,
                        $order->writer_assigned_at ? ['Writer assigned', $order->writer_assigned_at] : null,
                        $order->completed_at ? ['Submitted for review', $order->completed_at] : null,
                        $order->delivered_at ? ['Delivered', $order->delivered_at] : null,
                        $order->finished_at ? ['Finished', $order->finished_at] : null,
                        $order->cancelled_at ? ['Cancelled', $order->cancelled_at] : null,
                    ]) as $event)
                        <li class="flex justify-between gap-4 border-l border-slate-200 pl-3"><span>{{ $event[0] }}</span><span class="text-slate-500">{{ \Illuminate\Support\Carbon::parse($event[1])->timezone(config('app.timezone'))->format('j M Y, H:i') }}</span></li>
                    @endforeach
                </ol>
                <p class="mt-4 whitespace-pre-wrap text-[15px] leading-7">{{ $order->paper_details }}</p>
            </section>

            <section class="ui-card p-6">
                <h3 class="font-display text-xl tracking-tight">Bids</h3>
                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse ($order->bids->where('is_deleted', false) as $bid)
                        <li class="flex items-center justify-between gap-3 py-3 text-[13px]">
                            <span>{{ $bid->writer?->getFilamentName() ?? 'Writer #'.$bid->writer_id }} · {{ (int) $bid->bid_time }}h · ${{ number_format((float) $bid->amount, 2) }}</span>
                            @if ($can('orders.bids') && ! $bid->is_assigned)
                                <form method="POST" action="{{ route('office.orders.bids.approve', [$order, $bid]) }}">@csrf<button class="text-[13px] font-medium text-navy" type="submit">Approve</button></form>
                            @elseif ($bid->is_assigned)
                                <span class="pill pill-good">Assigned</span>
                            @endif
                        </li>
                    @empty
                        <li class="py-3 text-[13px] text-slate-500">No bids.</li>
                    @endforelse
                </ul>
            </section>

            <section class="ui-card p-6">
                <h3 class="font-display text-xl tracking-tight">Files</h3>
                <ul class="mt-3 divide-y divide-slate-100 text-[13px]">
                    @foreach ($order->files as $file)
                        <li class="flex items-center justify-between py-2">
                            <span>{{ $file->name }} · {{ $file->date_add }}</span>
                            @if ($can('files.download'))
                                <a class="font-medium text-navy" href="{{ route('office.orders.files.download', [$order, $file->file_id]) }}">Download</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($can('files.upload'))
                    <form class="mt-4 space-y-2" method="POST" action="{{ route('office.orders.files', $order) }}" enctype="multipart/form-data">
                        @csrf
                        <input name="upload" type="file" required class="text-[13px]">
                        <input name="description" placeholder="What is this version?" class="ui-input">
                        <button class="ui-btn ui-btn-primary" type="submit">Upload</button>
                    </form>
                @endif
                @if ((int) $order->writer_id === (int) $staff->employee_id && in_array((int) $order->status_id, [\App\Models\Order::WRITER_ASSIGNED, \App\Models\Order::WRITER_ACCEPTED], true))
                    <form class="mt-3" method="POST" action="{{ route('office.orders.submit', $order) }}">@csrf<button class="ui-btn ui-btn-quiet" type="submit">Submit for review</button></form>
                @endif
                @if ($can('files.approve') && (int) $order->status_id === \App\Models\Order::DONE)
                    <form class="mt-4 space-y-2" method="POST" action="{{ route('office.orders.return', $order) }}">
                        @csrf
                        <label class="ui-label" for="return-body">Return to the writer</label>
                        <textarea id="return-body" name="body" required class="ui-input h-auto py-3" rows="3"></textarea>
                        <button class="ui-btn ui-btn-quiet" type="submit">Return with notes</button>
                    </form>
                    <form class="mt-2" method="POST" action="{{ route('office.orders.approve', $order) }}">@csrf<button class="ui-btn ui-btn-primary" type="submit">Approve and deliver</button></form>
                @endif
            </section>

            <section class="ui-card p-6">
                <h3 class="font-display text-xl tracking-tight">Messages</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($order->messages->where('is_deleted', false) as $message)
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-[13px]">
                            <p class="text-slate-400">{{ \App\Support\Departments::NAMES[(int) $message->from_department] ?? 'Note' }} → {{ \App\Support\Departments::NAMES[(int) $message->to_department] ?? 'Note' }}</p>
                            <p class="mt-1 whitespace-pre-wrap">{{ $message->body }}</p>
                        </div>
                    @endforeach
                </div>
                @if ($can('messages.staff'))
                    <form class="mt-4 space-y-2" method="POST" action="{{ route('office.orders.messages', $order) }}">
                        @csrf
                        <select name="to" class="ui-input">
                            <option value="{{ \App\Support\Departments::SUPPORT }}">Support</option>
                            <option value="{{ \App\Support\Departments::WRITER }}">Writer</option>
                            @if ($can('messages.customer'))
                                <option value="{{ \App\Support\Departments::CUSTOMER }}">Customer</option>
                            @endif
                        </select>
                        <textarea name="body" required rows="3" class="ui-input h-auto py-3" placeholder="Write the message"></textarea>
                        <button class="ui-btn ui-btn-primary" type="submit">Send</button>
                    </form>
                @endif
            </section>
        </div>

        <aside class="space-y-4">
            @if ($can('orders.assign'))
                <form class="ui-card space-y-3 p-5" method="POST" action="{{ route('office.orders.assign', $order) }}">
                    @csrf
                    <h3 class="font-display text-lg tracking-tight">Assignment</h3>
                    <label class="ui-label" for="writer_id">Writer</label>
                    <select id="writer_id" name="writer_id" class="ui-input">
                        <option value="">Keep current</option>
                        @foreach ($writers as $writer)<option value="{{ $writer->employee_id }}">{{ $writer->getFilamentName() }}</option>@endforeach
                    </select>
                    <label class="ui-label" for="editor_id">Editor</label>
                    <select id="editor_id" name="editor_id" class="ui-input">
                        <option value="">Keep current</option>
                        @foreach ($editors as $editor)<option value="{{ $editor->employee_id }}">{{ $editor->getFilamentName() }}</option>@endforeach
                    </select>
                    <button class="ui-btn ui-btn-primary w-full" type="submit">Save assignment</button>
                </form>
            @endif
            @if ($can('orders.deadlines'))
                <form class="ui-card space-y-3 p-5" method="POST" action="{{ route('office.orders.deadline', $order) }}">
                    @csrf
                    <h3 class="font-display text-lg tracking-tight">Deadlines</h3>
                    <label class="ui-label" for="customer_deadline">Customer</label>
                    <input id="customer_deadline" type="datetime-local" name="customer_deadline" class="ui-input" required>
                    <label class="ui-label" for="writer_deadline">Writer</label>
                    <input id="writer_deadline" type="datetime-local" name="writer_deadline" class="ui-input" required>
                    <button class="ui-btn ui-btn-quiet w-full" type="submit">Update deadlines</button>
                </form>
            @endif
            <section class="ui-card p-5">
                <h3 class="font-display text-lg tracking-tight">Internal notes</h3>
                <ul class="mt-3 space-y-2 text-[13px]">
                    @foreach ($notes as $note)
                        <li><span class="text-slate-400">{{ $note->employee?->getFilamentName() }}</span> {{ $note->body }}</li>
                    @endforeach
                </ul>
                <form class="mt-3 space-y-2" method="POST" action="{{ route('office.orders.notes', $order) }}">
                    @csrf
                    <textarea name="body" required rows="3" class="ui-input h-auto py-3" placeholder="Only staff can see this"></textarea>
                    <button class="ui-btn ui-btn-quiet" type="submit">Save note</button>
                </form>
            </section>
            @if ($can('orders.cancel') || $can('orders.refund'))
                <form method="POST" action="{{ route('office.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                    @csrf
                    <button class="text-[13px] font-medium text-bad" type="submit">Cancel order</button>
                </form>
            @endif
        </aside>
    </div>
@endsection
