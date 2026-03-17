@extends('projectx::layouts.main')

@section('title', $title)

@section('content')
<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $form_action }}" id="projectx_shift_form">
            @csrf
            @if($is_edit)
                @method('PUT')
            @endif
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label">@lang('user.name')</label>
                    <input type="text" name="name" class="form-control form-control-solid" value="{{ $shift_name }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('essentials::lang.shift_type')</label>
                    <select name="type" id="shift_type" class="form-select form-select-solid" data-control="select2" data-hide-search="true" required>
                        <option value="fixed_shift" {{ $shift_type === 'fixed_shift' ? 'selected' : '' }}>@lang('essentials::lang.fixed_shift')</option>
                        <option value="flexible_shift" {{ $shift_type === 'flexible_shift' ? 'selected' : '' }}>@lang('essentials::lang.flexible_shift')</option>
                    </select>
                </div>
                <div class="col-md-6 shift-time-field">
                    <label class="form-label">@lang('restaurant.start_time')</label>
                    <input type="text"
                        name="start_time"
                        id="start_time"
                        class="form-control form-control-solid projectx-flatpickr-time"
                        value="{{ $shift_start_time }}">
                </div>
                <div class="col-md-6 shift-time-field">
                    <label class="form-label">@lang('restaurant.end_time')</label>
                    <input type="text"
                        name="end_time"
                        id="end_time"
                        class="form-control form-control-solid projectx-flatpickr-time"
                        value="{{ $shift_end_time }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">@lang('essentials::lang.holiday')</label>
                    <select name="holidays[]" class="form-select form-select-solid" data-control="select2" multiple>
                        @foreach($days as $day_key => $day_label)
                            <option value="{{ $day_key }}" {{ in_array($day_key, $shift_holidays, true) ? 'selected' : '' }}>
                                {{ $day_label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" id="is_allowed_auto_clockout" name="is_allowed_auto_clockout" value="1" {{ $shift_is_allowed_auto_clockout ? 'checked' : '' }}>
                        <span class="form-check-label">@lang('essentials::lang.allow_auto_clockout')</span>
                    </label>
                </div>
                <div class="col-md-6" id="auto_clockout_time_wrap">
                    <label class="form-label">@lang('essentials::lang.auto_clockout_time')</label>
                    <input type="text"
                        name="auto_clockout_time"
                        id="auto_clockout_time"
                        class="form-control form-control-solid projectx-flatpickr-time"
                        value="{{ $shift_auto_clockout_time }}">
                </div>
            </div>
            <div class="mt-7">
                <button type="submit" class="btn btn-primary">{{ $submit_label }}</button>
                <a href="{{ route('projectx.essentials.hrm.shift.index') }}" class="btn btn-light">@lang('messages.cancel')</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function () {
    $('.projectx-flatpickr-time').flatpickr({
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true
    });

    function toggleShiftTimeFields() {
        var isFixedShift = $('#shift_type').val() === 'fixed_shift';
        $('.shift-time-field').toggleClass('d-none', !isFixedShift);
    }

    function toggleAutoClockout() {
        var enabled = $('#is_allowed_auto_clockout').is(':checked');
        $('#auto_clockout_time_wrap').toggleClass('d-none', !enabled);
    }

    $('#shift_type').on('change', toggleShiftTimeFields);
    $('#is_allowed_auto_clockout').on('change', toggleAutoClockout);

    toggleShiftTimeFields();
    toggleAutoClockout();
})();
</script>
@endsection
