<div class="modal fade" id="update_task_status_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog" role="document">
  		<div class="modal-content">
  			<div class="modal-header">
		      	<button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
		      	<h4 class="modal-title">@lang( 'essentials::lang.change_status' )</h4>
		    </div>
		    <div class="modal-body">
	  			<div class="form-group">
					{!! Form::label('updated_status', __('sale.status') . ':') !!}
					{!! Form::select('status', $task_statuses, null, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;', 'id' => 'updated_status']); !!}
					{!! Form::hidden('task_id', null, ['id' => 'task_id']); !!}
				</div>
  			</div>
  			<div class="modal-footer">
		      	<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="update_status_btn">@lang( 'messages.update' )</button>
		      	<button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
		    </div>
  		</div>
  	</div>
</div>
