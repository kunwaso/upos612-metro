@extends('projectx::layouts.main')

@section('title', __('messages.view'))

@section('content')
<div class="card card-flush">
    <div class="card-body">
        <div><strong>@lang('essentials::lang.leave_type'):</strong> {{ $leave_type->leave_type }}</div>
        <div><strong>@lang('essentials::lang.max_leave_count'):</strong> {{ $leave_type->max_leave_count }}</div>
    </div>
</div>
@endsection
