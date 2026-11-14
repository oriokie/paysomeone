@extends('layouts.office')
@section('heading', 'Customers')
@section('content')
    <form method="GET" class="max-w-sm"><label class="sr-only" for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" class="ui-input" placeholder="Search email"></form>
    <div class="ui-card mt-4 divide-y divide-slate-100">
        @foreach ($customers as $customer)
            <div class="px-5 py-3">
                <p class="font-medium">{{ $reveal ? ($customer->name ?: 'Customer') : 'Customer #'.$customer->customer_id }}</p>
                <p class="text-[13px] text-slate-500">{{ $reveal ? $customer->email : 'Email hidden' }} @if($reveal && $customer->phone) · {{ $customer->phone }} @endif</p>
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
@endsection
