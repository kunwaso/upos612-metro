<div class="modal-dialog" role="document">
  	<div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\AccountTypeController::class, 'store']), 'method' => 'post', 'id' => 'account_type_form' ]) !!}
    <div class="modal-header">
      <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
      <h4 class="modal-title">@lang( 'lang_v1.add_account_type' )</h4>
    </div>

    <div class="modal-body">
      	<div class="form-group">
        	{!! Form::label('name', __( 'lang_v1.name' ) . ':*') !!}
          	{!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.name' )]); !!}
      	</div>

      <div class="form-group">
        	{!! Form::label('parent_account_type_id', __( 'lang_v1.parent_account_type' ) . ':') !!}
          	{!! Form::select('parent_account_type_id', $account_types->pluck('name', 'id'), null, ['class' => 'form-control', 'placeholder' => __( 'messages.please_select' )]); !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
