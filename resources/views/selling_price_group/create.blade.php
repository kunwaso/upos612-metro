<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\SellingPriceGroupController::class, 'store']), 'method' => 'post', 'id' => 'selling_price_group_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
      <h4 class="modal-title">@lang( 'lang_v1.add_selling_price_group' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'lang_v1.name' ) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.name' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'lang_v1.description' ) . ':') !!}
          {!! Form::textarea('description', null, ['class' => 'form-control','placeholder' => __( 'lang_v1.description' ), 'rows' => 3]); !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
