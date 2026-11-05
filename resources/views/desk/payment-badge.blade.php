@php
    $map = [
        'paid' => 'bg-good-soft text-good',
        'pending' => 'bg-wait-soft text-wait',
        'failed' => 'bg-bad-soft text-bad',
        'cancelled' => 'bg-slate-100 text-slate-600',
        'refunded' => 'bg-wait-soft text-wait',
    ];
@endphp
<span class="inline-flex rounded-full px-2.5 py-1 text-[13px] font-medium {{ $map[$status] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($status) }}</span>
