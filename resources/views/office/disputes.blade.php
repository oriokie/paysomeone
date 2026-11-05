@extends('layouts.office')
@section('heading', 'Disputes')
@section('content')
    <p class="text-[13px] text-slate-500">Open disputes come from paid orders. Approving returns the paid amount to the customer wallet.</p>
    <ul class="mt-6 space-y-4">
        @forelse ($disputes as $dispute)
            <li class="ui-card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="font-medium">#{{ $dispute->id }} · Order #{{ $dispute->order_id }} · {{ ucfirst($dispute->status) }}</p>
                    <p class="text-[13px] text-slate-500">{{ $dispute->customer?->name }}</p>
                </div>
                <p class="mt-3 whitespace-pre-wrap text-[15px]">{{ $dispute->reason }}</p>
                @if ($dispute->resolution)
                    <p class="mt-3 text-[13px] text-slate-500">{{ $dispute->resolution }}</p>
                @endif
                @if ($dispute->status === 'open')
                    <form class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]" method="POST" action="{{ route('office.disputes.resolve', $dispute) }}">
                        @csrf
                        <input name="resolution" required maxlength="2000" class="ui-input" placeholder="What you decided">
                        <button class="ui-btn ui-btn-primary" name="decision" value="approve" type="submit">Approve refund</button>
                        <button class="ui-btn ui-btn-quiet" name="decision" value="reject" type="submit">Reject</button>
                    </form>
                @endif
            </li>
        @empty
            <li class="ui-card px-6 py-12 text-center text-[13px] text-slate-500">No disputes yet.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $disputes->links() }}</div>
@endsection
