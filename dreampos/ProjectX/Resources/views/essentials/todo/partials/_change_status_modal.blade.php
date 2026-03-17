<div class="modal fade" id="projectx_todo_change_status_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">@lang('essentials::lang.change_status')</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="projectx_todo_change_status_form">
                    @csrf
                    <input type="hidden" name="todo_id" id="projectx_todo_change_status_todo_id">
                    <input type="hidden" name="only_status" value="1">
                    <div class="mb-4">
                        <label class="form-label required">@lang('essentials::lang.change_status')</label>
                        <select class="form-select form-select-solid" name="status" id="projectx_todo_change_status_status" data-control="select2" data-hide-search="true">
                            @foreach($task_statuses as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="projectx_todo_change_status_submit">@lang('messages.update')</button>
            </div>
        </div>
    </div>
</div>
