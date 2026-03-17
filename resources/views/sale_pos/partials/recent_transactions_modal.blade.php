<div class="modal fade no-print" id="recent_transactions_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')">
    <i class="ki-duotone ki-cross fs-2x">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
</button>
				<h4 class="modal-title">@lang('lang_v1.recent_transactions')</h4>
			</div>
			<div class="modal-body">
				<div class="nav-tabs-custom">
					<ul class="nav nav-tabs">
						<li class="active"><a href="#tab_final" data-toggle="tab" aria-expanded="true"><b><i class="fa fa-check"></i> @lang('sale.final')</b></a></li>

						<li class=""><a href="#tab_quotation" data-toggle="tab" aria-expanded="false"><b><i class="fa fa-terminal"></i> @lang('lang_v1.quotation')</b></a></li>
						
						<li class=""><a href="#tab_draft" data-toggle="tab" aria-expanded="false"><b><i class="fa fa-terminal"></i> @lang('sale.draft')</b></a></li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="tab_final">
						</div>
						<div class="tab-pane" id="tab_quotation">
						</div>
						<div class="tab-pane" id="tab_draft">
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
			    <button type="button" class="btn btn-light" data-bs-dismiss="modal" data-dismiss="modal">@lang('messages.close')</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div>
