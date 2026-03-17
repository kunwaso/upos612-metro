<div class="modal fade" tabindex="-1" role="dialog" id="confirmSuspendModal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
				<h4 class="modal-title">@lang('lang_v1.suspend_sale')</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-xs-12">
				        <div class="form-group">
				            {!! Form::label('additional_notes', __('lang_v1.suspend_note') . ':' ) !!}
				            {!! Form::textarea('additional_notes', !empty($transaction->additional_notes) ? $transaction->additional_notes : null, ['class' => 'form-control','rows' => '4']); !!}
				            {!! Form::hidden('is_suspend', 0, ['id' => 'is_suspend']); !!}
				        </div>
				    </div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="pos-suspend">@lang('messages.save')</button>
			    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang('messages.close')</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
