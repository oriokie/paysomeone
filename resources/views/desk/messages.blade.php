@extends('layouts.desk')
@section('title', 'Messages — '.$siteName)
@section('heading', 'Messages')

@section('content')
    <div class="ui-card divide-y divide-slate-100">
        @forelse ($messages as $message)
            <a class="flex gap-4 px-5 py-4 hover:bg-slate-50" href="{{ route('desk.order', $message->order_id) }}?tab=messages">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-100 text-[13px] font-semibold text-slate-600">{{ strtoupper(substr(\App\Support\Departments::NAMES[(int) $message->from_department] ?? 'N', 0, 1)) }}</span>
                <span class="min-w-0">
                    <span class="flex items-center gap-2 text-[13px] text-slate-500">
                        Order #{{ $message->order_id }}
                        <span>{{ $message->date_add ? \Illuminate\Support\Carbon::parse($message->date_add)->diffForHumans() : '' }}</span>
                    </span>
                    <span class="mt-1 block truncate text-[15px]">{{ $message->body }}</span>
                </span>
            </a>
        @empty
            <div class="px-5 py-16 text-center">
                <x-glyph name="message" class="mx-auto size-8 text-slate-300" />
                <p class="mt-3 font-display text-xl text-navy">No messages yet</p>
                <p class="mt-2 text-[13px] text-slate-500">Messages live on each order.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $messages->links() }}</div>
@endsection
