<div class="modal-dialog" role="document">
    {!! Form::open(['url' => action([\App\Http\Controllers\PurchaseOrderController::class, 'postEditPurchaseOrderStatus'], ['id' => $id]), 'method' => 'put', 'id' => 'update_po_status_form']) !!}
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
            <h4 class="modal-title">@lang('lang_v1.edit_status')</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        {!! Form::label('po_status', __('sale.status') . ':') !!}
                        <select name="status" id="po_status" class="form-control" style="width: 100%;">
                            @foreach($statuses as $key => $po_status)
                                <option value="{{$key}}" @if($key == $status) selected @endif>
                                    {{$po_status['label']}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">
                @lang('messages.close')
            </button>
            <button type="submit" class="btn btn-primary ladda-button">
                @lang('messages.update')
            </button>
        </div>
    </div><!-- /.modal-content -->
    {!! Form::close() !!}
</div><!-- /.modal-dialog -->

