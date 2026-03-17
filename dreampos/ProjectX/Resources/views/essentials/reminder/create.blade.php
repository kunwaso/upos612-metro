<div class="modal fade" id="projectx_reminder_create_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">@lang('essentials::lang.add_reminder')</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="projectx_reminder_create_form">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label required">@lang('essentials::lang.event_name')</label>
                        <input type="text" name="name" class="form-control form-control-solid" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label required">@lang('essentials::lang.date')</label>
                        <input type="text" name="date" class="form-control form-control-solid projectx-reminder-date" required>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label required">@lang('essentials::lang.time')</label>
                            <input type="text" name="time" class="form-control form-control-solid projectx-reminder-time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('essentials::lang.end_date')</label>
                            <input type="text" name="end_time" class="form-control form-control-solid projectx-reminder-time">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="form-label required">@lang('essentials::lang.repeat')</label>
                        <select name="repeat" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                            <option value="one_time">@lang('essentials::lang.one_time')</option>
                            <option value="every_day">@lang('essentials::lang.every_day')</option>
                            <option value="every_week">@lang('essentials::lang.every_week')</option>
                            <option value="every_month">@lang('essentials::lang.every_month')</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="projectx_reminder_create_submit">@lang('essentials::lang.submit')</button>
            </div>
        </div>
    </div>
</div>
