@extends('layouts.office')
@section('heading', 'Gateways')
@section('content')
    <p class="max-w-2xl text-slate-500">The same payment modules as the current office. Keys stay in the environment file. Only the last four characters are shown. Test mode and fees are stored here, not in the live gateway account.</p>
    <div class="mt-6 space-y-4">
        @foreach ($gateways as $key => $meta)
            @php $row = $saved[$key] ?? null; @endphp
            <form class="ui-card grid gap-3 p-5 md:grid-cols-[12rem_1fr]" method="POST" action="{{ route('office.gateways.update') }}">
                @csrf
                <input type="hidden" name="gateway" value="{{ $key }}">
                <div>
                    <p class="font-display text-lg tracking-tight">{{ $meta['label'] }}</p>
                    <p class="mt-1 font-mono text-[13px] text-slate-500">{{ \App\Support\Office\Gateways::mask($meta['env']) }}</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-4">
                    <label class="text-[13px]"><input type="checkbox" name="enabled" value="1" @checked($row?->enabled)> Enabled</label>
                    <label class="text-[13px]"><input type="checkbox" name="test_mode" value="1" @checked($row?->test_mode ?? true)> Test mode</label>
                    <input name="currency" value="{{ $row->currency ?? 'USD' }}" maxlength="3" class="ui-input h-10" aria-label="Currency for {{ $meta['label'] }}">
                    <input name="fee_percent" type="number" step="0.01" min="0" value="{{ $row->fee_percent ?? 0 }}" class="ui-input h-10" aria-label="Percent fee">
                    <input name="fee_fixed" type="number" step="0.01" min="0" value="{{ $row->fee_fixed ?? 0 }}" class="ui-input h-10" aria-label="Fixed fee">
                    <button class="ui-btn ui-btn-primary h-10" type="submit">Save</button>
                </div>
            </form>
        @endforeach
    </div>
@endsection
