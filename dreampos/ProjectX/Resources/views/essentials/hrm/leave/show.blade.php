@extends('projectx::layouts.main')

@section('title', __('messages.view'))

@section('content')
<div class="card card-flush">
    <div class="card-header"><h3 class="card-title">@lang('messages.view')</h3></div>
    <div class="card-body">
        <div><strong>@lang('essentials::lang.ref_no'):</strong> {{ $leave->ref_no }}</div>
        <div><strong>@lang('essentials::lang.status'):</strong> {{ $leave->status }}</div>
        <div><strong>@lang('essentials::lang.reason'):</strong> {{ $leave->reason }}</div>
    </div>
</div>
@endsection
