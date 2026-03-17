@extends('projectx::layouts.main')

@section('title', __('messages.edit'))

@section('content')
<div class="card card-flush">
    <div class="card-header"><h3 class="card-title">@lang('messages.edit')</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('projectx.essentials.hrm.leave-type.update', ['leave_type' => $leave_type->id]) }}">
            @csrf
            @method('PUT')
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label">@lang('essentials::lang.leave_type')</label>
                    <input type="text" name="leave_type" class="form-control form-control-solid" value="{{ $leave_type->leave_type }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.max_leave_count')</label>
                    <input type="number" name="max_leave_count" class="form-control form-control-solid" step="1" min="0" value="{{ $leave_type->max_leave_count }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.leave_count_interval')</label>
                    <input type="text" name="leave_count_interval" class="form-control form-control-solid" value="{{ $leave_type->leave_count_interval }}">
                </div>
            </div>
            <div class="mt-7">
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            </div>
        </form>
    </div>
</div>
@endsection
