<!-- Edit Order tax Modal -->
<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
			<h4 class="modal-title">@lang('lang_v1.view_invoice_url') - @lang('sale.invoice_no'): {{$transaction->invoice_no}}</h4>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<input type="text" class="form-control" value="{{$url}}" id="invoice_url">
				<p class="help-block">@lang('lang_v1.invoice_url_help')</p>
			</div>
		</div>
		<div class="modal-footer">
		    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">
		    	@lang('messages.close')
		    </button>

		    <a href="{{$url}}" id="view_invoice_url" target="_blank" rel="noopener" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
				@lang('messages.view')
			</a>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
	$('input#invoice_url').click(function(){
		$(this).select().focus();
	});
</script>
