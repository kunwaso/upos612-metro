<div class="modal fade" tabindex="-1" role="dialog" id="weighing_scale_modal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
				<h4 class="modal-title">@lang('lang_v1.weighing_scale')</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-xs-12">
				        <div class="form-group">
				            {!! Form::label('weighing_scale_barcode', __('lang_v1.weighing_scale_barcode') . ':' ) !!} @show_tooltip(__('lang_v1.weighing_scale_barcode_help'))
				            {!! Form::text('weighing_scale_barcode', null, ['class' => 'form-control']); !!}
				        </div>
				    </div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="weighing_scale_submit">@lang('messages.submit')</button>
			    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang('messages.close')</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
