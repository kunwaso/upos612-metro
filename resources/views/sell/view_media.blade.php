<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title">{{$title}}</h4>
			<button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
		</div>
		<div class="modal-body">
			@include('sell.partials.media_table')
		</div>
		<div class="modal-footer">
		    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang('messages.close')</button>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
