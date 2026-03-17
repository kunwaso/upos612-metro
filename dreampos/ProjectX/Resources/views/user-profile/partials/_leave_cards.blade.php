<div class="row g-7 mb-7">
    <div class="col-md-4">
        <div class="card card-flush h-100">
            <div class="card-body p-6">
                <div class="fs-5 fw-bold text-gray-900 mb-4">{{ __('projectx::lang.total_annual_leave') }}</div>
                <div class="fs-1 fw-bolder text-gray-900 mb-2">{{ $leave['annual_total'] }}</div>
                <div class="text-muted fw-semibold">
                    {{ $leave['is_available'] ? __('projectx::lang.leave_summary_note') : __('projectx::lang.leave_unavailable') }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-100">
            <div class="card-body p-6">
                <div class="fs-5 fw-bold text-gray-900 mb-4">{{ __('projectx::lang.leave_taken') }}</div>
                <div class="fs-1 fw-bolder text-gray-900 mb-2">{{ $leave['taken'] }}</div>
                <div class="text-muted fw-semibold">
                    {{ $leave['is_available'] ? __('projectx::lang.leave_summary_note') : __('projectx::lang.leave_unavailable') }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-100">
            <div class="card-body p-6">
                <div class="fs-5 fw-bold text-gray-900 mb-4">{{ __('projectx::lang.remaining_leave') }}</div>
                <div class="fs-1 fw-bolder text-gray-900 mb-2">{{ $leave['remaining'] }}</div>
                <div class="text-muted fw-semibold">
                    {{ $leave['is_available'] ? __('projectx::lang.leave_summary_note') : __('projectx::lang.leave_unavailable') }}
                </div>
            </div>
        </div>
    </div>
</div>
