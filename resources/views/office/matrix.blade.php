@extends('layouts.office')
@section('title', 'Roles — '.$siteName)
@section('heading', 'Roles')

@section('content')
    <p class="max-w-2xl text-slate-500">Admin always has every permission, so this matrix cannot lock the office. Sub-admin, editor, and writer start from the defaults until you save.</p>
    <form class="ui-card mt-6 overflow-x-auto" method="POST" action="{{ route('office.roles.save') }}">
        @csrf
        <table class="w-full min-w-[720px] text-left text-[13px]">
            <thead>
                <tr class="text-slate-500">
                    <th class="px-4 py-3 font-medium">Permission</th>
                    <th class="px-4 py-3 font-medium">Admin</th>
                    @foreach ($roles as $label)<th class="px-4 py-3 font-medium">{{ $label }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($catalog as $key => $label)
                    <tr class="border-t border-slate-100">
                        <th class="px-4 py-3 font-medium text-slate-900">{{ $label }}<span class="mt-0.5 block font-normal text-slate-400">{{ $key }}</span></th>
                        <td class="px-4 py-3 text-good">Always</td>
                        @foreach ($roles as $group => $role)
                            @php
                                $rows = $saved[$group] ?? null;
                                $on = $rows ? (bool) optional($rows->firstWhere('permission', $key))->allowed : in_array($key, $defaults[$group] ?? [], true);
                            @endphp
                            <td class="px-4 py-3">
                                <input type="checkbox" name="allow[{{ $group }}][{{ $key }}]" value="1" @checked($on) aria-label="{{ $role }} {{ $label }}">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-slate-100 p-4"><button class="ui-btn ui-btn-primary" type="submit">Save matrix</button></div>
    </form>
@endsection
