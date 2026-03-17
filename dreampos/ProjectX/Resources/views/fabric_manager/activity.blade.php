@extends('projectx::layouts.main')

@section('title', __('projectx::lang.fabric_activity'))

@section('content')
@include('projectx::fabric_manager._fabric_header')

<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3 class="fw-bold m-0">{{ __('projectx::lang.fabric_activity') }}</h3>
        </div>
        <div class="card-toolbar">
            <ul class="nav nav-stretch nav-line-tabs border-transparent" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active fs-5 fw-semibold" data-bs-toggle="tab" role="tab" href="#kt_activity_today">{{ __('projectx::lang.today') }}</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link fs-5 fw-semibold" data-bs-toggle="tab" role="tab" href="#kt_activity_week">{{ __('projectx::lang.week') }}</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link fs-5 fw-semibold" data-bs-toggle="tab" role="tab" href="#kt_activity_month">{{ __('projectx::lang.month') }}</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link fs-5 fw-semibold" data-bs-toggle="tab" role="tab" href="#kt_activity_year">{{ $activityYearLabel ?? now()->year }}</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div id="kt_activity_today" class="tab-pane fade show active" role="tabpanel">
                @include('projectx::fabric_manager._activity_timeline', ['logs' => $activityToday ?? collect(), 'fabric' => $fabric, 'canDeleteActivity' => $canDeleteActivity ?? false])
            </div>

            <div id="kt_activity_week" class="tab-pane fade" role="tabpanel">
                @include('projectx::fabric_manager._activity_timeline', ['logs' => $activityWeek ?? collect(), 'fabric' => $fabric, 'canDeleteActivity' => $canDeleteActivity ?? false])
            </div>

            <div id="kt_activity_month" class="tab-pane fade" role="tabpanel">
                @include('projectx::fabric_manager._activity_timeline', ['logs' => $activityMonth ?? collect(), 'fabric' => $fabric, 'canDeleteActivity' => $canDeleteActivity ?? false])
            </div>

            <div id="kt_activity_year" class="tab-pane fade" role="tabpanel">
                @include('projectx::fabric_manager._activity_timeline', ['logs' => $activityYear ?? collect(), 'fabric' => $fabric, 'canDeleteActivity' => $canDeleteActivity ?? false])
            </div>
        </div>
    </div>
</div>
@endsection
