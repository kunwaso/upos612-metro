<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\ExpenseCategoryController::class, 'update'], [$expense_category->id]), 'method' => 'PUT', 'id' => 'expense_category_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
      <h4 class="modal-title">@lang( 'expense.edit_expense_category' )</h4>
    </div>

    <div class="modal-body">
     <div class="form-group">
        {!! Form::label('name', __( 'expense.category_name' ) . ':*') !!}
          {!! Form::text('name', $expense_category->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'expense.category_name' )]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('code', __( 'expense.category_code' ) . ':') !!}
          {!! Form::text('code', $expense_category->code, ['class' => 'form-control', 'placeholder' => __( 'expense.category_code' )]); !!}
      </div>

        <div class="form-group">
            <div class="checkbox">
              <label>
                 {!! Form::checkbox('add_as_sub_cat', 1, !empty($expense_category->parent_id) ,[ 'class' => 'toggler', 'data-toggle_id' => 'parent_cat_div' ]); !!} @lang( 'lang_v1.add_as_sub_cat' )
              </label>
            </div>
        </div>
        <div class="form-group @if(empty($expense_category->parent_id)) hide @endif" id="parent_cat_div">
            {!! Form::label('parent_id', __( 'category.select_parent_category' ) . ':') !!}
            {!! Form::select('parent_id', $categories, $expense_category->parent_id, ['class' => 'form-control', 'placeholder' => __('lang_v1.none')]); !!}
        </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
