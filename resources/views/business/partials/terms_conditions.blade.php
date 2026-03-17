<div class="modal fade" id="tc_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fw-bolder">{{ __('lang_v1.terms_conditions') }}</h2>
                <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </button>
            </div>
            <div class="modal-body pt-5">
                @if (!empty($system_settings['superadmin_register_tc']))
                    {!! $system_settings['superadmin_register_tc'] !!}
                @endif
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light-primary" data-bs-dismiss="modal">
                    @lang('messages.close')
                </button>
            </div>
        </div>
    </div>
</div>
