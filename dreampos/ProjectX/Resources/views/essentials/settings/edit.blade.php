@extends('projectx::layouts.main')

@section('title', __('essentials::lang.essentials_n_hrm_settings'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.essentials_n_hrm_settings')</h1>
    </div>
</div>

<div class="card card-flush">
    <div class="card-body pt-7">
        <form method="POST" action="{{ route('projectx.essentials.settings.update') }}" id="projectx_essentials_settings_form">
            @csrf

            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-8 fs-6">
                <li class="nav-item">
                    <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#projectx_essentials_tab_leave">@lang('essentials::lang.leave')</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#projectx_essentials_tab_payroll">@lang('essentials::lang.payroll')</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#projectx_essentials_tab_attendance">@lang('essentials::lang.attendance')</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#projectx_essentials_tab_sales_target">@lang('essentials::lang.sales_target')</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#projectx_essentials_tab_essentials">@lang('essentials::lang.essentials')</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="projectx_essentials_tab_leave">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <label class="form-label">@lang('essentials::lang.leave_ref_no_prefix')</label>
                            <input type="text" name="leave_ref_no_prefix" class="form-control form-control-solid" value="{{ old('leave_ref_no_prefix', $settings['leave_ref_no_prefix'] ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">@lang('essentials::lang.leave_instructions')</label>
                            <textarea name="leave_instructions" id="leave_instructions" rows="5" class="form-control form-control-solid">{{ old('leave_instructions', $settings['leave_instructions'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="projectx_essentials_tab_payroll">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <label class="form-label">@lang('essentials::lang.payroll_ref_no_prefix')</label>
                            <input type="text" name="payroll_ref_no_prefix" class="form-control form-control-solid" value="{{ old('payroll_ref_no_prefix', $settings['payroll_ref_no_prefix'] ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="projectx_essentials_tab_attendance">
                    <div class="row g-5">
                        <div class="col-12">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_location_required" value="1" {{ old('is_location_required', $settings['is_location_required'] ?? 0) ? 'checked' : '' }}>
                                <span class="form-check-label text-gray-700">@lang('essentials::lang.is_location_required')</span>
                            </label>
                        </div>
                        <div class="col-12 text-gray-700 fw-semibold">@lang('essentials::lang.grace_time')</div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('essentials::lang.grace_before_checkin')</label>
                            <input type="number" min="0" step="1" name="grace_before_checkin" class="form-control form-control-solid" value="{{ old('grace_before_checkin', $settings['grace_before_checkin'] ?? '') }}">
                            <div class="text-muted fs-7 mt-1">@lang('essentials::lang.grace_before_checkin_help')</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('essentials::lang.grace_after_checkin')</label>
                            <input type="number" min="0" step="1" name="grace_after_checkin" class="form-control form-control-solid" value="{{ old('grace_after_checkin', $settings['grace_after_checkin'] ?? '') }}">
                            <div class="text-muted fs-7 mt-1">@lang('essentials::lang.grace_after_checkin_help')</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('essentials::lang.grace_before_checkout')</label>
                            <input type="number" min="0" step="1" name="grace_before_checkout" class="form-control form-control-solid" value="{{ old('grace_before_checkout', $settings['grace_before_checkout'] ?? '') }}">
                            <div class="text-muted fs-7 mt-1">@lang('essentials::lang.grace_before_checkout_help')</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('essentials::lang.grace_after_checkout')</label>
                            <input type="number" min="0" step="1" name="grace_after_checkout" class="form-control form-control-solid" value="{{ old('grace_after_checkout', $settings['grace_after_checkout'] ?? '') }}">
                            <div class="text-muted fs-7 mt-1">@lang('essentials::lang.grace_before_checkin_help')</div>
                        </div>
                        <div class="col-12 text-danger fs-7">
                            @lang('essentials::lang.allow_users_for_attendance_moved_to_role')
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="projectx_essentials_tab_sales_target">
                    <div class="row g-5">
                        <div class="col-12">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="calculate_sales_target_commission_without_tax" value="1" {{ old('calculate_sales_target_commission_without_tax', $settings['calculate_sales_target_commission_without_tax'] ?? 0) ? 'checked' : '' }}>
                                <span class="form-check-label text-gray-700">@lang('essentials::lang.calculate_sales_target_commission_without_tax')</span>
                            </label>
                            <div class="text-muted fs-7 mt-1">@lang('essentials::lang.calculate_sales_target_commission_without_tax_help')</div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="projectx_essentials_tab_essentials">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <label class="form-label">@lang('essentials::lang.essentials_todos_prefix')</label>
                            <input type="text" name="essentials_todos_prefix" class="form-control form-control-solid" value="{{ old('essentials_todos_prefix', $settings['essentials_todos_prefix'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-8">
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            </div>
        </form>
    </div>
</div>

<div class="text-muted fs-7 mt-6"><i>{!! __('essentials::lang.version_info', ['version' => $module_version]) !!}</i></div>
@endsection

@section('page_javascript')
<script>
(function () {
    if (window.tinymce) {
        window.tinymce.init({
            selector: 'textarea#leave_instructions'
        });
    }
})();
</script>
@endsection
