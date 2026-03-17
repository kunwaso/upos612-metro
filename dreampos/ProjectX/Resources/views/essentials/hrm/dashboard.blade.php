@extends('projectx::layouts.main')

@section('title', __('essentials::lang.hrm_dashboard'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.hrm_dashboard')</h1>
        <div class="text-muted fw-semibold fs-6">@lang('essentials::lang.hrm')</div>
    </div>
</div>

<div class="row g-5 g-xl-8">
    <div class="col-xl-3">
        <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="fs-2hx fw-bold text-gray-900 mb-2">{{ count($todays_leaves) }}</div>
                <div class="fs-6 fw-semibold text-muted">@lang('essentials::lang.leave')</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="fs-2hx fw-bold text-gray-900 mb-2">{{ count($todays_attendances) }}</div>
                <div class="fs-6 fw-semibold text-muted">@lang('essentials::lang.attendance')</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="fs-2hx fw-bold text-gray-900 mb-2">{{ count($upcoming_holidays) }}</div>
                <div class="fs-6 fw-semibold text-muted">@lang('essentials::lang.holiday')</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush h-md-100">
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="fs-2hx fw-bold text-gray-900 mb-2">{{ count($sales_targets) }}</div>
                <div class="fs-6 fw-semibold text-muted">@lang('essentials::lang.sales_target')</div>
            </div>
        </div>
    </div>
</div>
@endsection
