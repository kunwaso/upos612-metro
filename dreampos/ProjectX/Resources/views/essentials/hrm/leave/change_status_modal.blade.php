<div class="modal fade" id="projectx_leave_change_status_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">@lang('essentials::lang.change_status')</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="projectx_leave_change_status_form">
                    @csrf
                    <input type="hidden" name="leave_id" id="projectx_leave_change_status_leave_id">
                    <div class="mb-5">
                        <label class="form-label">@lang('essentials::lang.status')</label>
                        <select name="status" class="form-select form-select-solid">
                            <option value="pending">@lang('lang_v1.pending')</option>
                            <option value="approved">@lang('essentials::lang.approved')</option>
                            <option value="cancelled">@lang('essentials::lang.cancelled')</option>
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="form-label">@lang('essentials::lang.status_note')</label>
                        <textarea name="status_note" class="form-control form-control-solid"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-primary" id="projectx_leave_change_status_submit">@lang('messages.save')</button>
            </div>
        </div>
    </div>
</div>
