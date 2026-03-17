<div class="card card-flush mb-7">
    <div class="card-body p-6">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-5">
            <h3 class="fw-bold text-gray-900 mb-0">{{ $today_label }}</h3>
            <form method="POST" action="{{ url('/hrm/clock-in-clock-out') }}" class="projectx-essentials-json-form">
                @csrf
                <input type="hidden" name="type" value="{{ $attendance_today['is_clocked_in'] ? 'clock_out' : 'clock_in' }}">
                <input type="hidden" name="clock_in_out_location" value="">
                <button type="submit" class="btn btn-sm {{ $attendance_today['is_clocked_in'] ? 'btn-light-danger' : 'btn-primary' }}">
                    {{ $attendance_today['is_clocked_in'] ? __('projectx::lang.clock_out') : __('projectx::lang.clock_in') }}
                </button>
            </form>
        </div>

        @if(!$attendance_today['is_available'])
            <div class="alert alert-warning d-flex align-items-center mb-0">
                <i class="ki-duotone ki-information fs-2 me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                <span>{{ __('projectx::lang.attendance_unavailable') }}</span>
            </div>
        @else
            <div class="row g-0">
                <div class="col-md-4 pe-md-5 mb-4 mb-md-0">
                    <div class="text-muted fs-7 mb-2">{{ __('projectx::lang.start_work') }}</div>
                    <div class="fs-3 fw-bold text-gray-900">{{ $attendance_today['start_work'] }}</div>
                </div>
                <div class="col-md-4 px-md-5 border-start border-end mb-4 mb-md-0">
                    <div class="text-muted fs-7 mb-2">{{ __('projectx::lang.end_work') }}</div>
                    <div class="fs-3 fw-bold text-gray-900">{{ $attendance_today['end_work'] }}</div>
                </div>
                <div class="col-md-4 ps-md-5">
                    <div class="text-muted fs-7 mb-2">{{ __('projectx::lang.duration') }}</div>
                    <div class="fs-3 fw-bold text-gray-900">{{ $attendance_today['duration_human'] }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
