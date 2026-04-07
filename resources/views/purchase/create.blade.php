@extends('layouts.app')
@section('title', __('purchase.add_purchase'))

@section('content')
{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0 use_ai_btn">
                @lang('purchase.add_purchase')
                <i class="fa fa-keyboard hover-q text-muted ms-2 fs-5" aria-hidden="true"
                    data-container="body" data-toggle="popover" data-placement="bottom"
                    data-content="@include('purchase.partials.keyboard_shortcuts_details')"
                    data-html="true" data-trigger="hover"></i>
            </h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a>
                </li>
                <li class="breadcrumb-item text-gray-900">@lang('purchase.add_purchase')</li>
            </ul>
        </div>
    </div>
</div>
{{-- Content --}}
<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">

	<!-- Page level currency setting -->
	<input type="hidden" id="p_code" value="{{$currency_details->code}}">
	<input type="hidden" id="p_symbol" value="{{$currency_details->symbol}}">
	<input type="hidden" id="p_thousand" value="{{$currency_details->thousand_separator}}">
	<input type="hidden" id="p_decimal" value="{{$currency_details->decimal_separator}}">

	@include('layouts.partials.error')

	{!! Form::open(['url' => action([\App\Http\Controllers\PurchaseController::class, 'store']), 'method' => 'post', 'id' => 'add_purchase_form', 'files' => true ]) !!}
    <div class="form d-flex flex-column flex-lg-row">
        <div class="w-100 flex-lg-row-auto w-lg-300px mb-7 me-7 me-lg-10">
            @component('components.widget', ['class' => 'mb-7 py-4', 'title' => __('product.order_details')])
                <div class="d-flex flex-column gap-7">
                    <div class="fv-row">
                        {!! Form::label('supplier_id', __('purchase.supplier') . ':*', ['class' => 'form-label']) !!}
                        <div class="input-group flex-nowrap">
                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                            {!! Form::select('contact_id', [], null, ['class' => 'form-select form-select-solid', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'supplier_id']) !!}
                            <button type="button" class="btn btn-light add_new_supplier flex-shrink-0" data-name="">
                                <i class="fa fa-plus-circle text-primary"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <strong>@lang('business.address'):</strong>
                            <div id="supplier_address_div"></div>
                        </div>
                    </div>

                    <div class="fv-row">
                        {!! Form::label('ref_no', __('purchase.ref_no').':', ['class' => 'form-label']) !!}
                        @show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
                        {!! Form::text('ref_no', null, ['class' => 'form-control form-control-solid']) !!}
                    </div>

                    <div class="fv-row">
                        {!! Form::label('transaction_date', __('purchase.purchase_date') . ':*', ['class' => 'form-label']) !!}
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('transaction_date', format_datetime_value('now'), ['class' => 'form-control form-control-solid', 'readonly', 'required']) !!}
                        </div>
                    </div>

                    <div class="fv-row @if(!empty($default_purchase_status)) hide @endif">
                        {!! Form::label('status', __('purchase.purchase_status') . ':*', ['class' => 'form-label']) !!}
                        @show_tooltip(__('tooltip.order_status'))
                        {!! Form::select('status', $orderStatuses, $default_purchase_status, ['class' => 'form-select form-select-solid select2', 'placeholder' => __('messages.please_select'), 'required']) !!}
                    </div>

                    <div class="fv-row">
                        {!! Form::label('location_id', __('purchase.business_location').':*', ['class' => 'form-label']) !!}
                        @show_tooltip(__('tooltip.purchase_location'))
                        {!! Form::select('location_id', $business_locations, $location_config['default_location'], ['class' => 'form-select form-select-solid select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes) !!}
                    </div>

                    <div class="fv-row @if(!$currency_details->purchase_in_diff_currency) hide @endif">
                        {!! Form::label('exchange_rate', __('purchase.p_exchange_rate') . ':*', ['class' => 'form-label']) !!}
                        @show_tooltip(__('tooltip.currency_exchange_factor'))
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-info"></i></span>
                            {!! Form::number('exchange_rate', $currency_details->p_exchange_rate, ['class' => 'form-control form-control-solid', 'required', 'step' => 0.001]) !!}
                        </div>
                        <small class="text-danger">
                            @lang('purchase.diff_purchase_currency_help', ['currency' => $currency_details->name])
                        </small>
                    </div>

                    <div class="fv-row">
                        {!! Form::label('pay_term_number', __('contact.pay_term') . ':', ['class' => 'form-label']) !!}
                        @show_tooltip(__('tooltip.pay_term'))
                        <div class="row g-2">
                            <div class="col-6">
                                {!! Form::number('pay_term_number', null, ['class' => 'form-control form-control-solid', 'min' => 0, 'placeholder' => __('contact.pay_term')]) !!}
                            </div>
                            <div class="col-6">
                                {!! Form::select('pay_term_type', ['months' => __('lang_v1.months'), 'days' => __('lang_v1.days')], null, ['class' => 'form-select form-select-solid', 'placeholder' => __('messages.please_select'), 'id' => 'pay_term_type']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="fv-row">
                        {!! Form::label('document', __('purchase.attach_document') . ':', ['class' => 'form-label']) !!}
                        {!! Form::file('document', ['id' => 'upload_document', 'class' => 'form-control form-control-solid', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]) !!}
                        <small class="text-muted d-block mt-2">
                            @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                            @includeIf('components.document_help_text')
                        </small>
                    </div>

                    @foreach($purchase_custom_fields as $custom_field)
                        <div class="fv-row">
                            {!! Form::label($custom_field['input_name'], $custom_field['display_label'], ['class' => 'form-label']) !!}
                            {!! Form::text(
                                $custom_field['input_name'],
                                $custom_field['value'],
                                ['class' => 'form-control form-control-solid', 'placeholder' => $custom_field['placeholder'], 'required' => $custom_field['required']]
                            ) !!}
                        </div>
                    @endforeach

                    @if(!empty($common_settings['enable_purchase_order']))
                        <div class="fv-row">
                            {!! Form::label('purchase_order_ids', __('lang_v1.purchase_order').':', ['class' => 'form-label']) !!}
                            {!! Form::select('purchase_order_ids[]', [], null, ['class' => 'form-select form-select-solid select2', 'multiple', 'id' => 'purchase_order_ids']) !!}
                        </div>
                    @endif
                </div>
            @endcomponent
        </div>
        <div class="d-flex flex-column flex-lg-row-fluid gap-7 gap-lg-10">

	@component('components.widget', ['class' => 'mb-7', 'title' => __('product.sale_items')])
		<div class="row g-5">
			<div class="col-sm-12 missing-product-warning">
			</div>
			<div class="col-sm-2 text-center">
				<button type="button" class="btn btn-light-primary btn-sm" data-toggle="modal" data-target="#import_purchase_products_modal">@lang('product.import_products')</button>
			</div>
			<div class="col-sm-8">
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
		<div class="row g-5">
			<div class="col-sm-12">
				<div class="table-responsive">
					<table class="table align-middle table-row-dashed fs-6 gy-5" id="purchase_entry_table">
						<thead>
							<tr>
								<th>#</th>
								<th>@lang( 'product.product_name' )</th>
								<th>@lang( 'purchase.purchase_quantity' )</th>
								<th>@lang( 'lang_v1.unit_cost_before_discount' )</th>
								<th>@lang( 'lang_v1.discount_percent' )</th>
								<th>@lang( 'purchase.unit_cost_before_tax' )</th>
								<th class="{{ $ui_flags['hide_tax_class'] }}">@lang( 'purchase.subtotal_before_tax' )</th>
								<th class="{{ $ui_flags['hide_tax_class'] }}">@lang( 'purchase.product_tax' )</th>
								<th class="{{ $ui_flags['hide_tax_class'] }}">@lang( 'purchase.net_cost' )</th>
								<th>@lang( 'purchase.line_total' )</th>
								<th class="@if(empty($ui_flags['show_editing_product_from_purchase'])) hide @endif">
									@lang( 'lang_v1.profit_margin' )
								</th>
								<th>
									@lang( 'purchase.unit_selling_price' )
									<small>(@lang('product.inc_of_tax'))</small>
								</th>
								@if(!empty($ui_flags['show_lot_number']))
									<th>
										@lang('lang_v1.lot_number')
									</th>
								@endif
								@if(!empty($ui_flags['show_product_expiry']))
									<th>
										@lang('product.mfg_date') / @lang('product.exp_date')
									</th>
								@endif
								<th><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
				<hr/>
				<div class="offset-md-7 col-md-5">
					<table class="table table-sm table-row-bordered align-middle mb-0">
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

	@component('components.widget', ['class' => 'mb-7', 'title' => __('product.discount_tax_and_totals')])
		<div class="row g-5">
			<div class="col-sm-12">
			<table class="table">
				<tr>
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
				<tr>
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
	@component('components.widget', ['class' => 'mb-7', 'title' => __('product.shipping_details')])
	<div class="row g-5">
		<div class="col-md-4">
			<div class="form-group">
			{!! Form::label('shipping_details', __( 'purchase.shipping_details' ) . ':') !!}
			{!! Form::text('shipping_details', null, ['class' => 'form-control']); !!}
			</div>
		</div>
		<div class="col-md-4 offset-md-4">
			<div class="form-group">
				{!! Form::label('shipping_charges','(+) ' . __( 'purchase.additional_shipping_charges' ) . ':') !!}
				{!! Form::text('shipping_charges', 0, ['class' => 'form-control input_number', 'required']); !!}
			</div>
		</div>
	</div>
	<div class="row g-5">
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
		</div>
		<div class="row g-5">
			<div class="col-md-12 text-center">
				<button type="button" class="btn btn-light-primary btn-sm" id="toggle_additional_expense"> <i class="fas fa-plus"></i> @lang('lang_v1.add_additional_expenses') <i class="fas fa-chevron-down"></i></button>
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
								{!! Form::text('additional_expense_key_1', null, ['class' => 'form-control', 'id' => 'additional_expense_key_1']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_1', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_1']); !!}
							</td>
						</tr>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_2', null, ['class' => 'form-control', 'id' => 'additional_expense_key_2']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_2', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_2']); !!}
							</td>
						</tr>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_3', null, ['class' => 'form-control', 'id' => 'additional_expense_key_3']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_3', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_3']); !!}
							</td>
						</tr>
						<tr>
							<td>
								{!! Form::text('additional_expense_key_4', null, ['class' => 'form-control', 'id' => 'additional_expense_key_4']); !!}
							</td>
							<td>
								{!! Form::text('additional_expense_value_4', 0, ['class' => 'form-control input_number', 'id' => 'additional_expense_value_4']); !!}
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="row g-5">
			<div class="col-md-12 text-right">
				{!! Form::hidden('final_total', 0 , ['id' => 'grand_total_hidden']); !!}
						<b>@lang('purchase.purchase_total'): </b><span id="grand_total" class="display_currency" data-currency_symbol='true'>0</span>
			</div>
		</div>
	@endcomponent
	@component('components.widget', ['class' => 'mb-7', 'title' => __('product.payment_info')])
		<div class="payment_row">
			<div class="row g-5">
				<div class="col-md-12">
					<strong>@lang('lang_v1.advance_balance'):</strong> <span id="advance_balance_text">0</span>
					{!! Form::hidden('advance_balance', null, ['id' => 'advance_balance', 'data-error-msg' => __('lang_v1.required_advance_balance_not_available')]); !!}
				</div>
			</div>
			@include('sale_pos.partials.payment_row_form', ['row_index' => 0, 'show_date' => true, 'show_denomination' => true])
			<hr>
			<div class="row g-5">
				<div class="col-sm-12">
					<div class="text-end"><strong>@lang('purchase.payment_due'):</strong> <span id="payment_due">0.00</span></div>
				</div>
			</div>
			<br>
			<div class="row g-5">
				<div class="col-sm-12 text-center">
					<button type="button" id="submit_purchase_form" class="btn btn-primary btn-lg">@lang('messages.save')</button>
				</div>
			</div>
		</div>
	@endcomponent
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

@include('purchase.partials.import_purchase_products_modal')
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

			if($('.payment_types_dropdown').length){
				$('.payment_types_dropdown').change();
			}
			set_payment_type_dropdown();
			$('select#location_id').change(function() {
				set_payment_type_dropdown();
			});
    	});
    	$(document).on('change', '.payment_types_dropdown, #location_id', function(e) {
		    var default_accounts = $('select#location_id').length ? 
		                $('select#location_id')
		                .find(':selected')
		                .data('default_payment_accounts') : [];
		    var payment_types_dropdown = $('.payment_types_dropdown');
		    var payment_type = payment_types_dropdown.val();
		    var payment_row = payment_types_dropdown.closest('.payment_row');
	        var row_index = payment_row.find('.payment_row_index').val();

	        var account_dropdown = payment_row.find('select#account_' + row_index);
		    if (payment_type && payment_type != 'advance') {
		        var default_account = default_accounts && default_accounts[payment_type]['account'] ? 
		            default_accounts[payment_type]['account'] : '';
		        if (account_dropdown.length && default_accounts) {
		            account_dropdown.val(default_account);
		            account_dropdown.change();
		        }
		    }

		    if (payment_type == 'advance') {
		        if (account_dropdown) {
		            account_dropdown.prop('disabled', true);
		            account_dropdown.closest('.form-group').addClass('hide');
		        }
		    } else {
		        if (account_dropdown) {
		            account_dropdown.prop('disabled', false); 
		            account_dropdown.closest('.form-group').removeClass('hide');
		        }    
		    }
		});

		function set_payment_type_dropdown() {
			var payment_settings = $('#location_id').find(':selected').data('default_payment_accounts');
			payment_settings = payment_settings ? payment_settings : [];
			enabled_payment_types = [];
			for (var key in payment_settings) {
				if (payment_settings[key] && payment_settings[key]['is_enabled']) {
					enabled_payment_types.push(key);
				}
			}
			if (enabled_payment_types.length) {
				$(".payment_types_dropdown > option").each(function() {
					//skip if advance
					if ($(this).val() && $(this).val() != 'advance') {
						if (enabled_payment_types.indexOf($(this).val()) != -1) {
							$(this).removeClass('hide');
						} else {
							$(this).addClass('hide');
						}
					}
				});
			}
		}
	</script>
	@include('purchase.partials.keyboard_shortcuts')
@endsection

