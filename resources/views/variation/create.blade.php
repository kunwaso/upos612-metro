<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\VariationTemplateController::class, 'store']), 'method' => 'post', 'id' => 'variation_add_form', 'class' => 'form-horizontal' ]) !!}
    <div class="modal-header">
      <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
      <h4 class="modal-title">@lang('lang_v1.add_variation')</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name',__('lang_v1.variation_name') . ':*', ['class' => 'col-sm-3 control-label']) !!}

        <div class="col-sm-9">
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.variation_name')]); !!}
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-3 control-label">@lang('lang_v1.add_variation_values'):*</label>
        <div class="col-sm-7">
           {!! Form::text('variation_values[]', null, ['class' => 'form-control', 'required']); !!}
        </div>
        <div class="col-sm-2">
          <button type="button" class="btn btn-primary btn-sm" id="add_variation_values">+</button>
        </div>
      </div>
      <div id="variation_values"></div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang('messages.close')</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
