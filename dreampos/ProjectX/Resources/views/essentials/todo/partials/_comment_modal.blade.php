<div class="modal fade" id="projectx_todo_comment_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">@lang('essentials::lang.add_comment')</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="projectx_todo_comment_modal_form">
                    @csrf
                    <input type="hidden" name="task_id" id="projectx_todo_comment_task_id">
                    <div>
                        <label class="form-label required">@lang('essentials::lang.add_comment')</label>
                        <textarea name="comment" rows="4" class="form-control form-control-solid" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="projectx_todo_comment_modal_submit">@lang('essentials::lang.submit')</button>
            </div>
        </div>
    </div>
</div>
