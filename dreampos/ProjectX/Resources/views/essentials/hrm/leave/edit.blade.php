@extends('projectx::layouts.main')

@section('title', __('messages.edit'))

@section('content')
<div class="card card-flush">
    <div class="card-header"><h3 class="card-title">@lang('messages.edit')</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('projectx.essentials.hrm.leave.update', ['leave' => $leave->id]) }}">
            @csrf
            @method('PUT')
            <div class="row g-5">
                <div class="col-md-4">
                    <label class="form-label">@lang('essentials::lang.leave_type')</label>
                    <select name="essentials_leave_type_id" class="form-select form-select-solid" required>
                        @foreach($leave_types as $id => $label)
                            <option value="{{ $id }}" {{ (int)$leave->essentials_leave_type_id === (int)$id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('essentials::lang.leave_from')</label>
                    <input type="text" name="start_date" value="{{ \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d') }}" class="form-control form-control-solid projectx-flatpickr-date" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('essentials::lang.leave_to')</label>
                    <input type="text" name="end_date" value="{{ \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d') }}" class="form-control form-control-solid projectx-flatpickr-date" required>
                </div>
                @if(!empty($employees))
                <div class="col-md-12">
                    <label class="form-label">@lang('essentials::lang.employees')</label>
                    <select name="employees[]" class="form-select form-select-solid" data-control="select2" multiple>
                        @foreach($employees as $id => $label)
                            <option value="{{ $id }}" {{ (int)$leave->user_id === (int)$id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-12">
                    <label class="form-label">@lang('essentials::lang.reason')</label>
                    <textarea name="reason" class="form-control form-control-solid" rows="4">{{ $leave->reason }}</textarea>
                </div>
            </div>
            <div class="mt-7">
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                <a href="{{ route('projectx.essentials.hrm.leave.index') }}" class="btn btn-light">@lang('messages.cancel')</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
$('.projectx-flatpickr-date').flatpickr({dateFormat: 'Y-m-d'});
</script>
@endsection
