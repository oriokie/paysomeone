@extends('layouts.office')
@section('heading', 'Fines')
@section('content')
    <form class="ui-card grid gap-3 p-5 md:grid-cols-4" method="POST" action="{{ route('office.fines.types') }}">
        @csrf
        <input name="name" required placeholder="Name, for example Late delivery" class="ui-input">
        <select name="type" class="ui-input"><option value="amount">Fixed amount</option><option value="percentage">Percent of pay</option></select>
        <input name="value" type="number" step="0.01" min="0" required class="ui-input" placeholder="Value">
        <button class="ui-btn ui-btn-primary" type="submit">Add type</button>
    </form>
    <form class="ui-card mt-4 grid gap-3 p-5 md:grid-cols-5" method="POST" action="{{ route('office.fines.apply') }}">
        @csrf
        <select name="fine_type_id" class="ui-input" required>
            @foreach ($types as $type)<option value="{{ $type->fine_type_id }}">{{ $type->name }} ({{ $type->type }} {{ $type->value }})</option>@endforeach
        </select>
        <select name="employee_id" class="ui-input" required>
            @foreach ($writers as $writer)<option value="{{ $writer->employee_id }}">{{ $writer->getFilamentName() }}</option>@endforeach
        </select>
        <input name="order_id" placeholder="Order id" class="ui-input">
        <input name="base" type="number" step="0.01" placeholder="Base for a percent" class="ui-input">
        <button class="ui-btn ui-btn-primary" type="submit">Apply</button>
    </form>
    <ul class="ui-card mt-4 divide-y divide-slate-100">
        @forelse ($types as $type)
            <li class="px-5 py-3 text-[15px]">{{ $type->name }} · {{ $type->type }} · {{ $type->value }}</li>
        @empty
            <li class="px-5 py-8 text-[13px] text-slate-500">No fine types yet.</li>
        @endforelse
    </ul>
@endsection
