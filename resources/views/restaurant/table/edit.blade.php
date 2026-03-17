<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\Restaurant\TableController::class, 'update'], [$table->id]), 'method' => 'PUT', 'id' => 'table_edit_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
      <h4 class="modal-title">@lang( 'restaurant.edit_table' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'restaurant.table_name' ) . ':*') !!}
          {!! Form::text('name', $table->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'brand.brand_name' )]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'restaurant.short_description' ) . ':') !!}
          {!! Form::text('description', $table->description, ['class' => 'form-control','placeholder' => __( 'brand.short_description' )]); !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
