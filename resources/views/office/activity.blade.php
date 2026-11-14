@extends('layouts.office')
@section('heading', 'Activity')
@section('content')
    <ul class="ui-card divide-y divide-slate-100 text-[13px]">
        @foreach ($logs as $log)
            <li class="flex flex-wrap justify-between gap-2 px-5 py-3">
                <span><span class="font-medium">{{ $log->action }}</span> @if($log->subject_id) · {{ $log->subject_type }} {{ $log->subject_id }} @endif</span>
                <span class="text-slate-400">{{ $log->ip }} · {{ $log->created_at }}</span>
            </li>
        @endforeach
    </ul>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
