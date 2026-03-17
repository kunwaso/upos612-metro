<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\Modules\Essentials\Http\Controllers\EssentialsHolidayController::class, 'store']), 'method' => 'post', 'id' => 'add_holiday_form' ]) !!}

    <div class="modal-header border-0 pb-0">
      <h2 class="fw-bold mb-0">@lang( 'essentials::lang.add_holiday' )</h2>
      <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
        <i class="ki-duotone ki-cross fs-2x">
          <span class="path1"></span>
          <span class="path2"></span>
        </i>
      </button>
    </div>

    <div class="modal-body">
    	<div class="row">
    		<div class="form-group col-md-12">
	        	{!! Form::label('name', __( 'lang_v1.name' ) . ':*') !!}
	          	{!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.name' ), 'required']); !!}
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('start_date', __( 'essentials::lang.start_date' ) . ':*') !!}
	        	<div class="input-group data">
	        		{!! Form::text('start_date', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.start_date' ), 'readonly' ]); !!}
	        		<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
	        	</div>
	      	</div>

	      	<div class="form-group col-md-6">
	        	{!! Form::label('end_date', __( 'essentials::lang.end_date' ) . ':*') !!}
		        	<div class="input-group data">
		          	{!! Form::text('end_date', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.end_date' ), 'readonly', 'required' ]); !!}
		          	<span class="input-group-addon"><i class="fa fa-calendar"></i></span>
	        	</div>
	      	</div>

	      	<div class="form-group col-md-12">
	        	{!! Form::label('location_id', __( 'business.business_location' ) . ':') !!}
	          	{!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'placeholder' => __( 'lang_v1.all' ) ]); !!}
	      	</div>

	      	<div class="form-group col-md-12">
	        	{!! Form::label('note', __( 'brand.note' ) . ':') !!}
	          	{!! Form::textarea('note', null, ['class' => 'form-control', 'placeholder' => __( 'brand.note' ), 'rows' => 3 ]); !!}
	      	</div>
    	</div>
    </div>

    <div class="modal-footer border-0 pt-0">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
