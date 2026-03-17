<div class="modal fade" id="update_stock_transfer_status_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">

        {!! Form::open(['url' => "#", 'method' => 'post', 'id' => 'update_stock_transfer_status_form' ]) !!}

        <div class="modal-header">
          <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
          <h4 class="modal-title">@lang( 'lang_v1.update_status' ) @show_tooltip(__('lang_v1.completed_status_help'))</h4> 
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('update_status', __('sale.status').':*') !!}
                {!! Form::select('status', $statuses, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'update_status', 'style' => 'width: 100%;']); !!}
            </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.update' )</button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
