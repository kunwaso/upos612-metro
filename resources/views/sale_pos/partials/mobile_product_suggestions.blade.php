<div class="modal fade" id="mobile_product_suggestion_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	<!-- Edit Order tax Modal -->
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content bg-gray">
			<div class="modal-header">
				<button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
			</div>
			<div class="modal-body">
				@include('sale_pos.partials.pos_sidebar')
			</div>
			<div class="modal-footer">
			    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang('messages.close')</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
