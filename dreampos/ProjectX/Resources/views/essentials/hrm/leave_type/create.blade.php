@extends('projectx::layouts.main')

@section('title', __('messages.add'))

@section('content')
<div class="card card-flush">
    <div class="card-header"><h3 class="card-title">@lang('messages.add')</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('projectx.essentials.hrm.leave-type.store') }}">
            @csrf
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label">@lang('essentials::lang.leave_type')</label>
                    <input type="text" name="leave_type" class="form-control form-control-solid" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.max_leave_count')</label>
                    <input type="number" name="max_leave_count" class="form-control form-control-solid" step="1" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.leave_count_interval')</label>
                    <select name="leave_count_interval" class="form-select form-select-solid">
                        <option value="month">@lang('report.month')</option>
                        <option value="year">@lang('business.fy')</option>
                    </select>
                </div>
            </div>
            <div class="mt-7">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            </div>
        </form>
    </div>
</div>
@endsection
