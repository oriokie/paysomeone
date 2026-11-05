@extends('layouts.office')
@section('heading', 'Staff')
@section('content')
    <p class="text-slate-500">Suspend, category, and pool. There is no “view as” and no passwordless sign-in.</p>
    <div class="ui-card mt-6 divide-y divide-slate-100">
        @foreach ($people as $person)
            <form class="grid gap-3 px-5 py-4 md:grid-cols-[1.4fr_8rem_10rem_auto]" method="POST" action="{{ route('office.staff.update', $person) }}">
                @csrf
                <div>
                    <p class="font-medium">{{ $person->getFilamentName() }}</p>
                    <p class="text-[13px] text-slate-500">{{ $person->email }} · {{ \App\Services\Office\Permissions::roleName((int) $person->employee_group) }}</p>
                </div>
                <label class="text-[13px]"><input type="hidden" name="is_banned" value="0"><input type="checkbox" name="is_banned" value="1" @checked($person->is_banned)> Suspended</label>
                <select name="writer_category" class="ui-input h-10">
                    @foreach ([0 => 'Any', 2 => 'Best', 3 => 'ESL', 4 => 'ENL'] as $id => $label)
                        <option value="{{ $id }}" @selected((int) $person->writer_category === $id)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="ui-btn ui-btn-quiet h-10" type="submit">Save</button>
            </form>
        @endforeach
    </div>
    <div class="mt-4">{{ $people->links() }}</div>
@endsection
