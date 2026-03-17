<div class="modal fade" id="todays_profit_modal" tabindex="-1" aria-labelledby="todays_profit_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fs-3 fw-bold" id="todays_profit_modal_label">@lang('home.todays_profit')</h2>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="@lang('messages.close')">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
            </div>
            <div class="modal-body pt-5">
                <input type="hidden" id="modal_today" value="{{ \Carbon::now()->format('Y-m-d') }}">
                <div id="todays_profit" class="min-h-200px"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
