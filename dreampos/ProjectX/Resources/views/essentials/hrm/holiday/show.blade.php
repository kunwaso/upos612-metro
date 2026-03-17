@extends('projectx::layouts.main')

@section('title', __('essentials::lang.holiday'))

@section('content')
<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">{{ $holiday->name }}</h3>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <strong>@lang('essentials::lang.start_date'):</strong> {{ @format_date($holiday->start_date) }}
        </div>
        <div class="mb-3">
            <strong>@lang('essentials::lang.end_date'):</strong> {{ @format_date($holiday->end_date) }}
        </div>
        <div class="mb-3">
            <strong>@lang('brand.note'):</strong> {{ $holiday->note }}
        </div>
        <a href="{{ route('projectx.essentials.hrm.holiday.index') }}" class="btn btn-light">@lang('messages.back')</a>
    </div>
</div>
@endsection
