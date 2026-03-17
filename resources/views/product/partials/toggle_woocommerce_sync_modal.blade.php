<div class="modal fade" id ="woocommerce_sync_modal" tabindex="-1" role="dialog">
    {!! Form::open(['url' => action([\App\Http\Controllers\ProductController::class, 'toggleWooCommerceSync']), 'method' => 'post', 'id' => 'toggle_woocommerce_sync_form' ]) !!}
        <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
                <h4 class="modal-title" id="myModalLabel">
                    @lang('lang_v1.woocommerce_sync')
                </h4>
              </div>
              <div class="modal-body">
                <input type="hidden" id="woocommerce_products_sync" name="woocommerce_products_sync" value="">
                <div class="row">
                    <div class="col-md-12">
                        <label for="woocommerce_disable_sync">
                            @lang('lang_v1.woocommerce_sync')
                        </label>
                        <select name="woocommerce_disable_sync" class="form-control" id="woocommerce_disable_sync">
                            <option value="0">
                                @lang('lang_v1.enable')
                            </option>
                            <option value="1">
                                @lang('lang_v1.disable')
                            </option>
                        </select>
                    </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">
                    @lang('messages.close')
                </button>
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white ladda-button">
                    @lang('messages.save')
                </button>
              </div>
            </div>
        </div>
    {!! Form::close() !!}
</div>
