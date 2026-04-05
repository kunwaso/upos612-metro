@extends('layouts.app')
@section('title', __('lang_v1.add_purchase_order'))

@section('content')

{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">
                @lang('lang_v1.add_purchase_order')
                <i class="fa fa-keyboard-o hover-q text-muted ms-2 fs-5" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom" data-content="@include('purchase.partials.keyboard_shortcuts_details')" data-html="true" data-trigger="hover"></i>
            </h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600"><a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a></li>
                <li class="breadcrumb-item text-gray-900">@lang('lang_v1.add_purchase_order')</li>
            </ul>
        </div>
    </div>
</div>
<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">

	<!-- Page level currency setting -->
	<input type="hidden" id="p_code" value="{{$currency_details->code}}">
	<input type="hidden" id="p_symbol" value="{{$currency_details->symbol}}">
	<input type="hidden" id="p_thousand" value="{{$currency_details->thousand_separator}}">
	<input type="hidden" id="p_decimal" value="{{$currency_details->decimal_separator}}">

	@include('layouts.partials.error')

	{!! Form::open(['url' => action([\App\Http\Controllers\PurchaseOrderController::class, 'store']), 'method' => 'post', 'id' => 'add_purchase_form', 'files' => true ]) !!}
    <div class="form d-flex flex-column flex-lg-row">
        <div class="w-100 flex-lg-row-auto w-lg-300px mb-7 me-7 me-lg-10">
	@component('components.widget', ['class' => 'mb-7 py-4'])
		<input type="hidden" id="is_purchase_order">
		<div class="row">
			<div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
				<div class="form-group">
					{!! Form::label('supplier_id', __('purchase.supplier') . ':*') !!}
					<div class="input-group">
						<span class="input-group-text">
							<i class="fa fa-user"></i>
						</span>
						{!! Form::select('contact_id', [], null, ['class' => 'form-select form-select-solid', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'supplier_id']); !!}
						<span class="input-group-btn">
							<button type="button" class="btn btn-default bg-white btn-flat add_new_supplier" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
						</span>
					</div>
				</div>
				<strong>
					@lang('business.address'):
				</strong>
				<div id="supplier_address_div"></div>
			</div>
			<div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
				<div class="form-group">
					{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
					{!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
				</div>
			</div>
			<div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
				<div class="form-group">
					{!! Form::label('transaction_date', __('lang_v1.order_date') . ':*') !!}
					<div class="input-group">
						<span class="input-group-text">
							<i class="fa fa-calendar"></i>
						</span>
						{!! Form::text('transaction_date', format_datetime_value('now'), ['class' => 'form-control', 'readonly', 'required']); !!}
					</div>
				</div>
			</div>

			<div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
				<div class="form-group">
					{!! Form::label('delivery_date', __('lang_v1.delivery_date') . ':') !!}
					<div class="input-group">
						<span class="input-group-text">
							<i class="fa fa-calendar"></i>
						</span>
						{!! Form::text('delivery_date', null, ['class' => 'form-control']); !!}
					</div>
				</div>
			</div>
				
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('location_id', __('purchase.business_location').':*') !!}
					@show_tooltip(__('tooltip.purchase_location'))
					{!! Form::select('location_id', $business_locations, $location_config['default_location'], ['class' => 'form-select form-select-solid select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
				</div>
			</div>

			<!-- Currency Exchange Rate -->
			<div class="col-sm-3 @if(!$currency_details->purchase_in_diff_currency) hide @endif">
				<div class="form-group">
					{!! Form::label('exchange_rate', __('purchase.p_exchange_rate') . ':*') !!}
					@show_tooltip(__('tooltip.currency_exchange_factor'))
					<div class="input-group">
						<span class="input-group-text">
							<i class="fa fa-info"></i>
						</span>
						{!! Form::number('exchange_rate', $currency_details->p_exchange_rate, ['class' => 'form-control', 'required', 'step' => 0.001]); !!}
					</div>
					<span class="help-block text-danger">
						@lang('purchase.diff_purchase_currency_help', ['currency' => $currency_details->name])
					</span>
				</div>
			</div>

			<div class="col-md-3">
		          <div class="form-group">
		            <div class="multi-input">
		              {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
		              <br/>
		              {!! Form::number('pay_term_number', null, ['class' => 'form-control width-40 pull-left', 'placeholder' => __('contact.pay_term')]); !!}

		              {!! Form::select('pay_term_type', 
		              	['months' => __('lang_v1.months'), 
		              		'days' => __('lang_v1.days')], 
		              		null, 
		              	['class' => 'form-control width-60 pull-left','placeholder' => __('messages.please_select'), 'id' => 'pay_term_type']); !!}
		            </div>
		        </div>
		    </div>

			<div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                    {!! Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                    <p class="help-block">
                    	@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                    	@includeIf('components.document_help_text')
                    </p>
                </div>
            </div>
		</div>
		@if(!empty($common_settings['enable_purchase_requisition']))
		<div class="row">
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('purchase_requisition_ids', __('lang_v1.purchase_requisition').':') !!}
					{!! Form::select('purchase_requisition_ids[]', [], null, ['class' => 'form-select form-select-solid select2', 'multiple', 'id' => 'purchase_requisition_ids']); !!}
				</div>
			</div>
		</div>
		@endif
	@endcomponent
        </div>
        <div class="d-flex flex-column flex-lg-row-fluid gap-7 gap-lg-10">

	@component('components.widget', ['class' => 'mb-7 py-4'])
		<div class="row">
			<div class="col-sm-8 offset-sm-2">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-text">
							<i class="fa fa-search"></i>
						</span>
						{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'disabled' => $location_config['search_disable']]); !!}
					</div>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="form-group">
					<button tabindex="-1" type="button" class="btn btn-light-primary btn-sm btn-modal"data-href="{{action([\App\Http\Controllers\ProductController::class, 'quickAdd'])}}" 
            	data-container=".quick_add_product_modal"><i class="fa fa-plus"></i> @lang( 'product.add_new_product' ) </button>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<div class="table-responsive">
					<table class="table align-middle table-row-dashed fs-6 gy-5" id="purchase_entry_table">
						<thead>
							<tr>
								<th>#</th>
								<th>@lang( 'product.product_name' )</th>
								<th>@lang( 'lang_v1.order_quantity' )</th>
								<th>@lang( 'lang_v1.unit_cost_before_discount' )</th>
								<th>@lang( 'lang_v1.discount_percent' )</th>
								<th>@lang( 'purchase.unit_cost_before_tax' )</th>
								<th class="{{ $ui_flags['hide_tax_class'] }}">@lang( 'purchase.subtotal_before_tax' )</th>
								<th class="{{ $ui_flags['hide_tax_class'] }}">@lang( 'purchase.product_tax' )</th>
								<th class="{{ $ui_flags['hide_tax_class'] }}">@lang( 'purchase.net_cost' )</th>
								<th>@lang( 'purchase.line_total' )</th>
								<th class="hide">
									@lang( 'lang_v1.profit_margin' )
								</th>
								<th><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
				<hr/>
				<div class="offset-md-7 col-md-5">
					<table class="table table-sm table-row-bordered align-middle mb-0 w-100">
						<tr>
							<th class="col-md-7 text-right">@lang( 'lang_v1.total_items' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_quantity" class="display_currency" data-currency_symbol="false"></span>
							</td>
						</tr>
						<tr class="hide">
							<th class="col-md-7 text-right">@lang( 'purchase.total_before_tax' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_st_before_tax" class="display_currency"></span>
								<input type="hidden" id="st_before_tax_input" value=0>
							</td>
						</tr>
						<tr>
							<th class="col-md-7 text-right">@lang( 'purchase.net_total_amount' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_subtotal" class="display_currency"></span>
								<!-- This is total before purchase tax-->
								<input type="hidden" id="total_subtotal_input" value=0  name="total_before_tax">
							</td>
						</tr>
					</table>
				</div>

				<input type="hidden" id="row_count" value="0">
			</div>
		</div>
	@endcomponent

	@component('components.widget', ['class' => 'mb-7 py-4'])
	<div class="row">
		<div class="col-md-4">
			<div class="form-group">
	            {!! Form::label('shipping_details', __('sale.shipping_details')) !!}
	            {!! Form::textarea('shipping_details',null, ['class' => 'form-control','placeholder' => __('sale.shipping_details') ,'rows' => '3', 'cols'=>'30']); !!}
	        </div>
		</div>
		<div class="col-md-4">
			<div class="form-group">
	            {!! Form::label('shipping_address', __('lang_v1.shipping_address')) !!}
	            {!! Form::textarea('shipping_address',null, ['class' => 'form-control','placeholder' => __('lang_v1.shipping_address') ,'rows' => '3', 'cols'=>'30']); !!}
	        </div>
		</div>
		<div class="col-md-4">
			<div class="form-group">
				{!!Form::label('shipping_charges', __('sale.shipping_charges'))!!}
				<div class="input-group">
				<span class="input-group-text">
				<i class="fa fa-info"></i>
				</span>
				{!!Form::text('shipping_charges',num_format_value(0.00),['class'=>'form-control input_number','placeholder'=> __('sale.shipping_charges')]);!!}
				</div>
			</div>
		</div>
		<div class="clearfix"></div>
		<div class="col-md-4">
			<div class="form-group">
	            {!! Form::label('shipping_status', __('lang_v1.shipping_status')) !!}
	            {!! Form::select('shipping_status',$shipping_statuses, null, ['class' => 'form-select form-select-solid','placeholder' => __('messages.please_select')]); !!}
	        </div>
		</div>
		<div class="col-md-4">
	        <div class="form-group">
	            {!! Form::label('delivered_to', __('lang_v1.delivered_to') . ':' ) !!}
	            {!! Form::text('delivered_to', null, ['class' => 'form-control','placeholder' => __('lang_v1.delivered_to')]); !!}
	        </div>
	    </div>
        @foreach($shipping_custom_fields as $shipping_custom_field)
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label($shipping_custom_field['input_name'], $shipping_custom_field['display_label']) !!}
                    {!! Form::text(
                        $shipping_custom_field['input_name'],
                        $shipping_custom_field['value'],
                        ['class' => 'form-control', 'placeholder' => $shipping_custom_field['placeholder'], 'required' => $shipping_custom_field['required']]
                    ) !!}
                </div>
            </div>
        @endforeach
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('shipping_documents', __('lang_v1.shipping_documents') . ':') !!}
                {!! Form::file('shipping_documents[]', ['id' => 'shipping_documents', 'multiple', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                <p class="help-block">
                	@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                	@includeIf('components.document_help_text')
                </p>
            </div>
        </div>        
	</div>
	<div class="row">
			<div class="col-md-12 text-center">
				<button type="button" class="btn btn-primary btn-sm" id="toggle_additional_expense"> <i class="fas fa-plus"></i> @lang('lang_v1.add_additional_expenses') <i class="fas fa-chevron-down"></i></button>
			</div>
			<div class="col-md-8 offset-md-4" id="additional_expenses_div" style="display: none;">
				<table class="table table-sm table-row-bordered align-middle">
					<thead>
						<tr>
							<th>@lang('lang_v1.additional_expense_name')</th>
							<th>@lang('sale.amount')</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_1', null, ['class' => 'form-control']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_1', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_1']); !!}
							</td>
						</tr>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_2', null, ['class' => 'form-control']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_2', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_2']); !!}
							</td>
						</tr>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_3', null, ['class' => 'form-control']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_3', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_3']); !!}
							</td>
						</tr>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_4', null, ['class' => 'form-control']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_4', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_4']); !!}
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	<div class="row">
		<div class="col-md-4 offset-md-8">
	    {!! Form::hidden('final_total', 0 , ['id' => 'grand_total_hidden']); !!}
		<b>@lang('lang_v1.order_total'): </b><span id="grand_total" class="display_currency" data-currency_symbol='true'>0</span>
		</div>
	</div>
	@endcomponent

	@component('components.widget', ['class' => 'mb-7 py-4'])
		<div class="row">
			<div class="col-sm-12">
			<table class="table">
				<tr class="hide">
					<td class="col-md-3">
						<div class="form-group">
							{!! Form::label('discount_type', __( 'purchase.discount_type' ) . ':') !!}
							{!! Form::select('discount_type', [ '' => __('lang_v1.none'), 'fixed' => __( 'lang_v1.fixed' ), 'percentage' => __( 'lang_v1.percentage' )], '', ['class' => 'form-select form-select-solid select2']); !!}
						</div>
					</td>
					<td class="col-md-3">
						<div class="form-group">
						{!! Form::label('discount_amount', __( 'purchase.discount_amount' ) . ':') !!}
						{!! Form::text('discount_amount', 0, ['class' => 'form-control input_number', 'required']); !!}
						</div>
					</td>
					<td class="col-md-3">
						&nbsp;
					</td>
					<td class="col-md-3">
						<b>@lang( 'purchase.discount' ):</b>(-) 
						<span id="discount_calculated_amount" class="display_currency">0</span>
					</td>
				</tr>
				<tr class="hide">
					<td>
						<div class="form-group">
						{!! Form::label('tax_id', __('purchase.purchase_tax') . ':') !!}
						<select name="tax_id" id="tax_id" class="form-select form-select-solid select2" placeholder="'Please Select'">
							<option value="" data-tax_amount="0" data-tax_type="fixed" selected>@lang('lang_v1.none')</option>
							@foreach($taxes as $tax)
								<option value="{{ $tax->id }}" data-tax_amount="{{ $tax->amount }}" data-tax_type="{{ $tax->calculation_type }}">{{ $tax->name }}</option>
							@endforeach
						</select>
						{!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
						</div>
					</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>
						<b>@lang( 'purchase.purchase_tax' ):</b>(+) 
						<span id="tax_calculated_amount" class="display_currency">0</span>
					</td>
				</tr>
				<tr>
					<td colspan="4">
						<div class="form-group">
							{!! Form::label('additional_notes',__('purchase.additional_notes')) !!}
							{!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
						</div>
					</td>
				</tr>

			</table>
			</div>
		</div>
	@endcomponent
	<div class="row">
			<div class="col-sm-12 text-center">
				<button type="button" id="submit_purchase_form" class="btn btn-primary btn-lg">@lang('messages.save')</button>
			</div>
		</div>
    </div>
    </div>

{!! Form::close() !!}
    </div>
</div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
@endsection

@section('javascript')
	<script src="{{ asset('assets/app/js/purchase.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('assets/app/js/product.js?v=' . $asset_v) }}"></script>
	<script type="text/javascript">
		$(document).ready( function(){
      		__page_leave_confirmation('#add_purchase_form');
      		$('.paid_on').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });

            $('#shipping_documents').fileinput({
		        showUpload: false,
		        showPreview: false,
		        browseLabel: LANG.file_browse_label,
		        removeLabel: LANG.remove,
		    });

			if($('#location_id').length){
				$('#location_id').change();
			}
    	});
	</script>
	@include('purchase.partials.keyboard_shortcuts')
@endsection

