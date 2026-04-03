<div class="modal-dialog modal-xl" role="document">
	<div class="modal-content">
	{!! Form::open(['url' => action([\App\Http\Controllers\OpeningStockController::class, 'save']), 'method' => 'post', 'id' => 'add_opening_stock_form' ]) !!}
	{!! Form::hidden('product_id', $product->id); !!}
		<div class="modal-header">
		    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary no-print" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')"><i class="ki-duotone ki-cross fs-2x"><span class="path1"></span><span class="path2"></span></i></button>
		      <h4 class="modal-title" id="modalTitle">@lang('lang_v1.add_opening_stock')</h4>
	    </div>
	    <div class="modal-body">
			@include('opening_stock.form-part')
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-primary" id="add_opening_stock_btn">@lang('messages.save')</button>
		    <button type="button" class="btn btn-sm btn-light no-print" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
		 </div>
	 {!! Form::close() !!}
	</div>
</div>

