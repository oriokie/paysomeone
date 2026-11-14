@extends('layouts.checkout')
@section('title', 'Apply to write — '.$siteName)
@section('content')
    <section class="mx-auto max-w-[720px] px-5 py-12">
        <p class="text-[13px] font-medium text-slate-500">Writers</p>
        <h1 class="mt-2 font-display text-[32px] tracking-tight text-navy">Apply to write</h1>
        <p class="mt-3 text-slate-500">A short grammar check and one sample. This is professional writing, not student work. Approval turns the application into a writer account.</p>
        @if (session('status'))<p class="mt-4 rounded-2xl bg-good-soft px-4 py-3 text-[15px] text-good" role="status">{{ session('status') }}</p>@endif
        <form class="ui-card mt-6 space-y-4 p-6" method="POST" action="{{ route('apply.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="ui-label" for="first_name">First name</label><input id="first_name" name="first_name" value="{{ old('first_name') }}" required class="ui-input"></div>
                <div><label class="ui-label" for="last_name">Last name</label><input id="last_name" name="last_name" value="{{ old('last_name') }}" required class="ui-input"></div>
            </div>
            <div><label class="ui-label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required class="ui-input"></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="ui-label" for="password">Password</label><input id="password" name="password" type="password" required class="ui-input"></div>
                <div><label class="ui-label" for="password_confirmation">Confirm</label><input id="password_confirmation" name="password_confirmation" type="password" required class="ui-input"></div>
            </div>
            @foreach ($questions as $index => $question)
                <fieldset>
                    <legend class="ui-label">{{ $question['prompt'] }}</legend>
                    @foreach ($question['choices'] as $choice)
                        <label class="mt-1 flex items-center gap-2 text-[15px]"><input type="radio" name="answers[{{ $index }}]" value="{{ $choice }}" required> {{ $choice }}</label>
                    @endforeach
                </fieldset>
            @endforeach
            <div>
                <label class="ui-label" for="sample">Sample (pdf, doc, or txt)</label>
                <input id="sample" name="sample" type="file" required class="text-[13px]">
            </div>
            @if ($errors->any())<p class="text-[13px] text-bad" role="alert">{{ $errors->first() }}</p>@endif
            <button class="ui-btn ui-btn-primary" type="submit">Submit application</button>
        </form>
    </section>
@endsection
