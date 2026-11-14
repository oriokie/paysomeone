@extends('layouts.office')
@section('heading', 'Applications')
@section('content')
    <div class="space-y-4">
        @forelse ($applicants as $person)
            <article class="ui-card p-5">
                <p class="font-medium">{{ $person->getFilamentName() }}</p>
                <p class="text-[13px] text-slate-500">{{ $person->email }} · {{ $person->writing_prompt_answer }}</p>
                <form class="mt-3 flex flex-wrap items-end gap-3" method="POST" action="{{ route('office.applications.decide', $person) }}">
                    @csrf
                    <select name="writer_category" class="ui-input h-10 w-36">
                        <option value="2">Best</option><option value="3">ESL</option><option value="4">ENL</option>
                    </select>
                    <select name="academic_level" class="ui-input h-10 w-36">
                        <option value="0">No level</option><option value="1">Level 1</option><option value="2">Level 2</option>
                    </select>
                    <button class="ui-btn ui-btn-primary h-10" name="decision" value="approve" type="submit">Approve</button>
                    <button class="ui-btn ui-btn-quiet h-10" name="decision" value="reject" type="submit">Reject</button>
                </form>
            </article>
        @empty
            <p class="text-[13px] text-slate-500">No completed applications.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $applicants->links() }}</div>
@endsection
