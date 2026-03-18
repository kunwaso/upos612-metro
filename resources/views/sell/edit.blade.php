@extends('layouts.app')

@php
	$title = $transaction->type == 'sales_order' ? __('lang_v1.edit_sales_order') : __('sale.edit_sale');
@endphp
@section('title', $title)

@section('content')
<div class="no-print pb-10">
<div class="d-flex flex-wrap flex-stack pb-5 mb-5">
	<div>
		<h1 class="fs-2 fw-bold text-gray-900">{{ $title }}</h1>
		<div class="text-muted fw-semibold fs-6">
			@if($transaction->type == 'sales_order') @lang('restaurant.order_no') @else @lang('sale.invoice_no') @endif:
			<span class="text-success fw-bold">#{{ $transaction->invoice_no }}</span>
		</div>
	</div>
</div>
<input type="hidden" id="amount_rounding_method" value="{{ $pos_settings['amount_rounding_method'] ?? 'none' }}">
@if(!empty($pos_settings['allow_overselling']))
	<input type="hidden" id="is_overselling_allowed">
@endif
@if(session('business.enable_rp') == 1)
    <input type="hidden" id="reward_point_enabled">
@endif
@php
	$custom_labels = json_decode(session('business.custom_labels'), true);
	$common_settings = session()->get('business.common_settings');
@endphp
<input type="hidden" id="item_addition_method" value="{{ $business_details->item_addition_method }}">
{!! Form::open(['url' => action([\App\Http\Controllers\SellPosController::class, 'update'], ['po' => $transaction->id ]), 'method' => 'put', 'id' => 'edit_sell_form', 'files' => true, 'data-transaction-id' => $transaction->id ]) !!}

	<div class="row g-5">
		<div class="col-12">
			<div class="card card-flush shadow-sm mb-5">
				<div class="card-header border-0 pt-5">
					<h3 class="card-title align-items-start flex-column">
						<span class="card-label fw-bold fs-4 text-gray-800">{{ __('invoice.invoice_settings') }}</span>
					</h3>
				</div>
				<div class="card-body pt-0">
				<div class="row g-4">
				{!! Form::hidden('location_id', $transaction->location_id, ['id' => 'location_id', 'data-receipt_printer_type' => !empty($location_printer_type) ? $location_printer_type : 'browser', 'data-default_payment_accounts' => $transaction->location->default_payment_accounts]); !!}

	@if($transaction->type == 'sales_order')
	 	<input type="hidden" id="sale_type" value="{{ $transaction->type }}">
	@endif

				@if(!empty($transaction->selling_price_group_id))
					<div class="col-md-4">
						<div class="mb-5">
							<label class="form-label">@lang('lang_v1.price_group')</label>
							<div class="input-group input-group-solid">
								<span class="input-group-text">
									<i class="ki-duotone ki-dollar fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
								</span>
								{!! Form::hidden('price_group', $transaction->selling_price_group_id, ['id' => 'price_group']) !!}
								{!! Form::text('price_group_text', $transaction->price_group->name, ['class' => 'form-control form-control-solid', 'readonly']); !!}
								<span class="input-group-text">
									@show_tooltip(__('lang_v1.price_group_help_text'))
								</span>
							</div>
						</div>
					</div>
				@endif

				@if(in_array('types_of_service', $enabled_modules) && !empty($transaction->types_of_service))
					<div class="col-md-4">
						<div class="mb-5">
							<label class="form-label">@lang('lang_v1.types_of_service')</label>
							<div class="input-group input-group-solid">
								<span class="input-group-text">
									<i class="ki-duotone ki-abstract-26 fs-2 text-primary service_modal_btn"><span class="path1"></span><span class="path2"></span></i>
								</span>
								{!! Form::text('types_of_service_text', $transaction->types_of_service->name, ['class' => 'form-control form-control-solid', 'readonly']); !!}
								{!! Form::hidden('types_of_service_id', $transaction->types_of_service_id, ['id' => 'types_of_service_id']) !!}
								<span class="input-group-text">
									@show_tooltip(__('lang_v1.types_of_service_help'))
								</span>
							</div>
							<p class="text-muted fs-7 mb-0 @if(empty($transaction->selling_price_group_id)) hide @endif" id="price_group_text">@lang('lang_v1.price_group'): <span>@if(!empty($transaction->selling_price_group_id)){{ $transaction->price_group->name }}@endif</span></p>
						</div>
					</div>
					<div class="modal fade types_of_service_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
						@if(!empty($transaction->types_of_service))
						@include('types_of_service.pos_form_modal', ['types_of_service' => $transaction->types_of_service])
						@endif
					</div>
				@endif

				@if(in_array('subscription', $enabled_modules))
					<div class="col-md-4 ms-md-auto">
						<div class="form-check form-check-custom form-check-solid mb-5">
							{!! Form::checkbox('is_recurring', 1, $transaction->is_recurring, ['class' => 'form-check-input input-icheck', 'id' => 'is_recurring']); !!}
							<label class="form-check-label" for="is_recurring">@lang('lang_v1.subscribe')?</label>
							<button type="button" data-toggle="modal" data-target="#recurringInvoiceModal" class="btn btn-sm btn-light-primary ms-2"><i class="ki-duotone ki-exit-right fs-3"><span class="path1"></span><span class="path2"></span></i></button>
							@show_tooltip(__('lang_v1.recurring_invoice_help'))
						</div>
					</div>
				@endif
				<div class="@if(!empty($commission_agent)) col-md-3 @else col-md-4 @endif">
					<div class="mb-5">
						{!! Form::label('contact_id', __('contact.customer') . ':*', ['class' => 'form-label required']) !!}
						<div class="input-group input-group-solid">
							<span class="input-group-text">
								<i class="ki-duotone ki-user fs-2"><span class="path1"></span><span class="path2"></span></i>
							</span>
							<input type="hidden" id="default_customer_id" value="{{ $transaction->contact->id }}">
							<input type="hidden" id="default_customer_name" value="{{ $transaction->contact->name }}">
							{!! Form::select('contact_id', [], null, ['class' => 'form-select mousetrap', 'id' => 'customer_id', 'placeholder' => 'Enter Customer name / phone', 'required', 'style' => 'min-width: 0;']); !!}
							<button type="button" class="btn btn-light-primary add_new_customer" data-name=""><i class="ki-duotone ki-plus-circle fs-2"><span class="path1"></span><span class="path2"></span></i></button>
						</div>
						<div class="text-danger fs-7 mt-2 @if(empty($customer_due)) hide @endif contact_due_text"><strong>@lang('account.customer_due'):</strong> <span>{{ $customer_due ?? '' }}</span></div>
					</div>
					<div class="fs-7 text-gray-700 mt-2">
						<strong>@lang('lang_v1.billing_address'):</strong>
						<div id="billing_address_div">{!! $transaction->contact->contact_address ?? '' !!}</div>
						<br>
						<strong>@lang('lang_v1.shipping_address'):</strong>
						<div id="shipping_address_div">
							{!! $transaction->contact->supplier_business_name ?? '' !!},<br>
							{!! $transaction->contact->name ?? '' !!},<br>
							{!! $transaction->contact->shipping_address ?? '' !!}
						</div>
					</div>
				</div>

				<div class="col-md-3">
					@php
						$is_pay_term_required = !empty($pos_settings['is_pay_term_required']);
					@endphp
					<div class="mb-5">
						{!! Form::label('pay_term_number', __('contact.pay_term') . ':', ['class' => 'form-label']) !!} @show_tooltip(__('tooltip.pay_term'))
						<div class="d-flex flex-wrap gap-2">
							{!! Form::number('pay_term_number', $transaction->pay_term_number, ['class' => 'form-control form-control-solid', 'style' => 'max-width: 8rem;', 'placeholder' => __('contact.pay_term'), 'required' => $is_pay_term_required]); !!}
							{!! Form::select('pay_term_type', ['months' => __('lang_v1.months'), 'days' => __('lang_v1.days')], $transaction->pay_term_type, ['class' => 'form-select form-select-solid', 'placeholder' => __('messages.please_select'), 'required' => $is_pay_term_required, 'style' => 'max-width: 10rem;']); !!}
						</div>
					</div>
				</div>

				@if(!empty($commission_agent))
				@php
					$is_commission_agent_required = !empty($pos_settings['is_commission_agent_required']);
				@endphp
				<div class="col-md-3">
					<div class="mb-5">
						{!! Form::label('commission_agent', __('lang_v1.commission_agent') . ':', ['class' => 'form-label' . ($is_commission_agent_required ? ' required' : '')]) !!}
						{!! Form::select('commission_agent', $commission_agent, $transaction->commission_agent, ['class' => 'form-select form-select-solid select2', 'id' => 'commission_agent', 'required' => $is_commission_agent_required]); !!}
					</div>
				</div>
				@endif
				<div class="@if(!empty($commission_agent)) col-md-3 @else col-md-4 @endif">
					<div class="mb-5">
						{!! Form::label('transaction_date', __('sale.sale_date') . ':*', ['class' => 'form-label required']) !!}
						<div class="input-group input-group-solid">
							<span class="input-group-text">
								<i class="ki-duotone ki-calendar fs-2"><span class="path1"></span><span class="path2"></span></i>
							</span>
							{!! Form::text('transaction_date', $transaction->transaction_date, ['class' => 'form-control form-control-solid', 'readonly', 'required']); !!}
						</div>
					</div>
				</div>
				@php
					if ($transaction->status == 'draft' && $transaction->is_quotation == 1) {
						$status = 'quotation';
					} elseif ($transaction->status == 'draft' && $transaction->sub_status == 'proforma') {
						$status = 'proforma';
					} else {
						$status = $transaction->status;
					}
				@endphp
				@if($transaction->type == 'sales_order')
					<input type="hidden" name="status" id="status" value="{{ $transaction->status }}">
				@else
					<div class="@if(!empty($commission_agent)) col-md-3 @else col-md-4 @endif">
						<div class="mb-5">
							{!! Form::label('status', __('sale.status') . ':*', ['class' => 'form-label required']) !!}
							{!! Form::select('status', $statuses, $status, ['class' => 'form-select form-select-solid select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
						</div>
					</div>
				@endif
				@if(isset($status) && in_array($status, ['draft', 'quotation']))
					<input type="hidden" id="disable_qty_alert">
				@endif
				@if($transaction->status == 'draft')
				<div class="col-md-3">
					<div class="mb-5">
						{!! Form::label('invoice_scheme_id', __('invoice.invoice_scheme') . ':', ['class' => 'form-label']) !!}
						{!! Form::select('invoice_scheme_id', $invoice_schemes, $default_invoice_schemes->id, ['class' => 'form-select form-select-solid select2', 'placeholder' => __('messages.please_select')]); !!}
					</div>
				</div>
				@endif
				@can('edit_invoice_number')
				<div class="col-md-3">
					<div class="mb-5">
						{!! Form::label('invoice_no', $transaction->type == 'sales_order' ? __('restaurant.order_no') : __('sale.invoice_no') . ':', ['class' => 'form-label']) !!}
						{!! Form::text('invoice_no', $transaction->invoice_no, ['class' => 'form-control form-control-solid', 'placeholder' => $transaction->type == 'sales_order' ? __('restaurant.order_no') : __('sale.invoice_no')]); !!}
						<p class="text-muted fs-7 mb-0">@lang('lang_v1.keep_blank_to_autogenerate')</p>
					</div>
				</div>
				@endcan
				@php
			        $custom_field_1_label = !empty($custom_labels['sell']['custom_field_1']) ? $custom_labels['sell']['custom_field_1'] : '';

			        $is_custom_field_1_required = !empty($custom_labels['sell']['is_custom_field_1_required']) && $custom_labels['sell']['is_custom_field_1_required'] == 1 ? true : false;

			        $custom_field_2_label = !empty($custom_labels['sell']['custom_field_2']) ? $custom_labels['sell']['custom_field_2'] : '';

			        $is_custom_field_2_required = !empty($custom_labels['sell']['is_custom_field_2_required']) && $custom_labels['sell']['is_custom_field_2_required'] == 1 ? true : false;

			        $custom_field_3_label = !empty($custom_labels['sell']['custom_field_3']) ? $custom_labels['sell']['custom_field_3'] : '';

			        $is_custom_field_3_required = !empty($custom_labels['sell']['is_custom_field_3_required']) && $custom_labels['sell']['is_custom_field_3_required'] == 1 ? true : false;

			        $custom_field_4_label = !empty($custom_labels['sell']['custom_field_4']) ? $custom_labels['sell']['custom_field_4'] : '';

			        $is_custom_field_4_required = !empty($custom_labels['sell']['is_custom_field_4_required']) && $custom_labels['sell']['is_custom_field_4_required'] == 1 ? true : false;
		        @endphp
		        @if(!empty($custom_field_1_label))
		        	@php
		        		$label_1 = $custom_field_1_label . ':';
		        		if($is_custom_field_1_required) { $label_1 .= '*'; }
		        	@endphp
		        	<div class="col-md-4">
				        <div class="mb-5">
				            {!! Form::label('custom_field_1', $label_1, ['class' => 'form-label' . ($is_custom_field_1_required ? ' required' : '')]) !!}
				            {!! Form::text('custom_field_1', $transaction->custom_field_1, ['class' => 'form-control form-control-solid', 'placeholder' => $custom_field_1_label, 'required' => $is_custom_field_1_required]); !!}
				        </div>
				    </div>
		        @endif
		        @if(!empty($custom_field_2_label))
		        	@php
		        		$label_2 = $custom_field_2_label . ':';
		        		if($is_custom_field_2_required) { $label_2 .= '*'; }
		        	@endphp
		        	<div class="col-md-4">
				        <div class="mb-5">
				            {!! Form::label('custom_field_2', $label_2, ['class' => 'form-label' . ($is_custom_field_2_required ? ' required' : '')]) !!}
				            {!! Form::text('custom_field_2', $transaction->custom_field_2, ['class' => 'form-control form-control-solid', 'placeholder' => $custom_field_2_label, 'required' => $is_custom_field_2_required]); !!}
				        </div>
				    </div>
		        @endif
		        @if(!empty($custom_field_3_label))
		        	@php
		        		$label_3 = $custom_field_3_label . ':';
		        		if($is_custom_field_3_required) { $label_3 .= '*'; }
		        	@endphp
		        	<div class="col-md-4">
				        <div class="mb-5">
				            {!! Form::label('custom_field_3', $label_3, ['class' => 'form-label' . ($is_custom_field_3_required ? ' required' : '')]) !!}
				            {!! Form::text('custom_field_3', $transaction->custom_field_3, ['class' => 'form-control form-control-solid', 'placeholder' => $custom_field_3_label, 'required' => $is_custom_field_3_required]); !!}
				        </div>
				    </div>
		        @endif
		        @if(!empty($custom_field_4_label))
		        	@php
		        		$label_4 = $custom_field_4_label . ':';
		        		if($is_custom_field_4_required) { $label_4 .= '*'; }
		        	@endphp
		        	<div class="col-md-4">
				        <div class="mb-5">
				            {!! Form::label('custom_field_4', $label_4, ['class' => 'form-label' . ($is_custom_field_4_required ? ' required' : '')]) !!}
				            {!! Form::text('custom_field_4', $transaction->custom_field_4, ['class' => 'form-control form-control-solid', 'placeholder' => $custom_field_4_label, 'required' => $is_custom_field_4_required]); !!}
				        </div>
				    </div>
		        @endif
		        <div class="col-md-4">
	                <div class="mb-5">
	                    {!! Form::label('upload_document', __('purchase.attach_document') . ':', ['class' => 'form-label']) !!}
	                    {!! Form::file('sell_document', ['id' => 'upload_document', 'class' => 'form-control form-control-solid', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
	                    <p class="text-muted fs-7 mb-0 mt-2">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
	                    @includeIf('components.document_help_text')</p>
	                </div>
	            </div>
		        @if((!empty($pos_settings['enable_sales_order']) && $transaction->type != 'sales_order') || $is_order_request_enabled)
					<div class="col-md-4">
						<div class="mb-5">
							{!! Form::label('sales_order_ids', __('lang_v1.sales_order').':', ['class' => 'form-label']) !!}
							{!! Form::select('sales_order_ids[]', $sales_orders, $transaction->sales_order_ids, ['class' => 'form-select form-select-solid select2 not_loaded', 'multiple', 'id' => 'sales_order_ids']); !!}
						</div>
					</div>
				@endif
		        @if(in_array('tables' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
		        	<div class="col-12"><span id="restaurant_module_span" data-transaction_id="{{ $transaction->id }}"></span></div>
		        @endif
				</div>
				</div>
			</div>

			<div class="card card-flush shadow-sm mb-5">
				<div class="card-header border-0 pt-5">
					<h3 class="card-title align-items-start flex-column">
						<span class="card-label fw-bold fs-4 text-gray-800">@lang('sale.product')</span>
					</h3>
				</div>
				<div class="card-body pt-0">
				<div class="row g-4">
					<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="{{ $business_details->sell_price_tax }}">
					<input type="hidden" id="product_row_count" value="{{ count($sell_details) }}">
					@php
						$hide_tax = session()->get('business.enable_inline_tax') == 0 ? 'hide' : '';
					@endphp
					<div class="col-12">
					<div class="table-responsive border border-gray-300 rounded">
					<table class="table table-row-bordered table-row-gray-300 align-middle gy-4 gs-0 mb-0" id="pos_table">
						<thead>
							<tr>
								<th class="text-center">#</th>
								<th class="text-center">@lang('sale.product')</th>
								<th class="text-center">@lang('sale.qty')</th>
								@if(!empty($pos_settings['inline_service_staff']))
									<th class="text-center">@lang('restaurant.service_staff')</th>
								@endif
								<th class="@if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">@lang('sale.unit_price')</th>
								<th class="@if(!auth()->user()->can('edit_product_discount_from_sale_screen')) hide @endif">@lang('receipt.discount')</th>
								<th class="text-center {{ $hide_tax }}">@lang('sale.tax')</th>
								<th class="text-center {{ $hide_tax }}">@lang('sale.price_inc_tax')</th>
								@if(!empty($common_settings['enable_product_warranty']))
									<th>@lang('lang_v1.warranty')</th>
								@endif
								<th class="text-center">@lang('sale.subtotal')</th>
								<th class="text-center w-50px"><i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i></th>
							</tr>
						</thead>
						<tbody>
							@foreach($sell_details as $sell_line)
								@include('sale_pos.product_row', ['product' => $sell_line, 'row_count' => $loop->index, 'tax_dropdown' => $taxes, 'sub_units' => !empty($sell_line->unit_details) ? $sell_line->unit_details : [], 'action' => 'edit', 'is_direct_sell' => true, 'so_line' => $sell_line->so_line, 'is_sales_order' => $transaction->type == 'sales_order', 'is_serial_no' => true])
							@endforeach
						</tbody>
					</table>
					</div>
					<div class="table-responsive mb-5">
					<table class="table table-row-bordered align-middle gy-2 gs-0 mb-0">
						<tr>
							<td class="text-end py-3">
								<span class="fw-bold text-gray-800">@lang('sale.item'):</span>
								<span class="total_quantity ms-2">0</span>
								<span class="fw-bold text-gray-800 ms-5">@lang('sale.total'):</span>
								<span class="price_total ms-2 fw-semibold">0</span>
							</td>
						</tr>
					</table>
					</div>
					</div>
					<div class="col-12 col-lg-10 mx-auto">
						<label class="form-label fw-semibold text-gray-800 mb-2" for="search_product">@lang('lang_v1.search_product_placeholder')</label>
						<div class="input-group input-group-solid input-group-lg">
							<button type="button" class="btn btn-light-primary" data-toggle="modal" data-target="#configure_search_modal" title="{{ __('lang_v1.configure_product_search') }}" aria-label="{{ __('lang_v1.configure_product_search') }}">
								<i class="ki-duotone ki-filter-search fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
							</button>
							{!! Form::text('search_product', null, ['class' => 'form-control form-control-solid mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus' => true]); !!}
							<button type="button" class="btn btn-light-success pos_add_quick_product" data-href="{{ action([\App\Http\Controllers\ProductController::class, 'quickAdd']) }}" data-container=".quick_add_product_modal" title="@lang('product.add_product')">
								<i class="ki-duotone ki-plus-circle fs-2"><span class="path1"></span><span class="path2"></span></i>
							</button>
						</div>
					</div>
				</div>
				</div>
			</div>

			<div class="card card-flush shadow-sm mb-5">
				<div class="card-header border-0 pt-5">
					<h3 class="card-title align-items-start flex-column">
						<span class="card-label fw-bold fs-4 text-gray-800">@lang('receipt.discount') & @lang('sale.order_tax')</span>
					</h3>
				</div>
				<div class="card-body pt-0">
				<div class="row g-4">
				<div class="col-md-4 @if($transaction->type == 'sales_order') hide @endif">
			        <div class="mb-5">
			            {!! Form::label('discount_type', __('sale.discount_type') . ':*', ['class' => 'form-label required']) !!}
			            <div class="input-group input-group-solid">
			                <span class="input-group-text">
			                    <i class="ki-duotone ki-discount fs-2"><span class="path1"></span><span class="path2"></span></i>
			                </span>
			                {!! Form::select('discount_type', ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], $transaction->discount_type, ['class' => 'form-select form-select-solid', 'placeholder' => __('messages.please_select'), 'required', 'data-default' => 'percentage']); !!}
			            </div>
			        </div>
			    </div>
			    @php
			    	$max_discount = !is_null(auth()->user()->max_sales_discount_percent) ? auth()->user()->max_sales_discount_percent : '';
			    @endphp
			    <div class="col-md-4 @if($transaction->type == 'sales_order') hide @endif">
			        <div class="mb-5">
			            {!! Form::label('discount_amount', __('sale.discount_amount') . ':*', ['class' => 'form-label required']) !!}
			            <div class="input-group input-group-solid">
			                <span class="input-group-text">
			                    <i class="ki-duotone ki-calculator fs-2"><span class="path1"></span><span class="path2"></span></i>
			                </span>
			                {!! Form::text('discount_amount', num_format_value($transaction->discount_amount), ['class' => 'form-control form-control-solid input_number', 'data-default' => $business_details->default_sales_discount, 'data-max-discount' => $max_discount, 'data-max-discount-error_msg' => __('lang_v1.max_discount_error_msg', ['discount' => $max_discount != '' ? num_format_value($max_discount) : '']) ]); !!}
			            </div>
			        </div>
			    </div>
			    <div class="col-md-4 @if($transaction->type == 'sales_order') hide @endif d-flex align-items-end mb-5">
			    	<div class="p-4 bg-light-primary rounded w-100">
			    		<span class="fw-semibold text-gray-800">@lang('sale.discount_amount'):</span> <span class="text-danger">(-)</span>
						<span class="display_currency fw-bold" id="total_discount">0</span>
					</div>
			    </div>
			    <div class="col-12 p-5 rounded bg-light @if(session('business.enable_rp') != 1 || $transaction->type == 'sales_order') hide @endif">
			    	<input type="hidden" name="rp_redeemed" id="rp_redeemed" value="{{ $transaction->rp_redeemed }}">
			    	<input type="hidden" name="rp_redeemed_amount" id="rp_redeemed_amount" value="{{ $transaction->rp_redeemed_amount }}">
			    	<div class="row g-4">
			    	<div class="col-12"><h4 class="fs-5 fw-bold text-gray-900">{{ session('business.rp_name') }}</h4></div>
			    	<div class="col-md-4">
				        <div class="mb-0">
				            {!! Form::label('rp_redeemed_modal', __('lang_v1.redeemed') . ':', ['class' => 'form-label']) !!}
				            <div class="input-group input-group-solid">
				                <span class="input-group-text">
				                    <i class="ki-duotone ki-gift fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
				                </span>
				                {!! Form::number('rp_redeemed_modal', $transaction->rp_redeemed, ['class' => 'form-control form-control-solid direct_sell_rp_input', 'data-amount_per_unit_point' => session('business.redeem_amount_per_unit_rp'), 'min' => 0, 'data-max_points' => !empty($redeem_details['points']) ? $redeem_details['points'] : 0, 'data-min_order_total' => session('business.min_order_total_for_redeem') ]); !!}
				                <input type="hidden" id="rp_name" value="{{ session('business.rp_name') }}">
				            </div>
				        </div>
				    </div>
				    <div class="col-md-4 d-flex align-items-end">
				    	<p class="mb-0"><strong>@lang('lang_v1.available'):</strong> <span id="available_rp">{{ $redeem_details['points'] ?? 0 }}</span></p>
				    </div>
				    <div class="col-md-4 d-flex align-items-end">
				    	<p class="mb-0"><strong>@lang('lang_v1.redeemed_amount'):</strong> <span class="text-danger">(-)</span><span id="rp_redeemed_amount_text">{{ num_format_value($transaction->rp_redeemed_amount) }}</span></p>
				    </div>
				    </div>
			    </div>
			    <div class="col-md-4 @if($transaction->type == 'sales_order') hide @endif">
			    	<div class="mb-5">
			            {!! Form::label('tax_rate_id', __('sale.order_tax') . ':*', ['class' => 'form-label required']) !!}
			            <div class="input-group input-group-solid">
			                <span class="input-group-text">
			                    <i class="ki-duotone ki-percentage fs-2"><span class="path1"></span><span class="path2"></span></i>
			                </span>
			                {!! Form::select('tax_rate_id', $taxes['tax_rates'], $transaction->tax_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-select form-select-solid', 'data-default'=> $business_details->default_sales_tax], $taxes['attributes']); !!}
							<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount" value="{{ num_format_value($transaction->tax?->amount) }}" data-default="{{ $business_details->tax_calculation_amount }}">
			            </div>
			        </div>
			    </div>
			    <div class="col-md-4 ms-md-auto @if($transaction->type == 'sales_order') hide @endif d-flex align-items-end mb-5">
			    	<div class="p-4 bg-light-info rounded w-100">
			    		<span class="fw-semibold text-gray-800">@lang('sale.order_tax'):</span> <span class="text-success">(+)</span>
						<span class="display_currency fw-bold" id="order_tax">{{ $transaction->tax_amount }}</span>
					</div>
			    </div>
			    <div class="col-md-12">
			    	<div class="mb-5">
						{!! Form::label('sell_note', __('sale.sell_note'), ['class' => 'form-label']) !!}
						{!! Form::textarea('sale_note', $transaction->additional_notes, ['class' => 'form-control form-control-solid', 'rows' => 3]); !!}
					</div>
			    </div>
			    <input type="hidden" name="is_direct_sale" value="1">
				<input type="hidden" name="is_serial_no" value="1">
				</div>
				</div>
			</div>

			<div class="card card-flush shadow-sm mb-5">
				<div class="card-header border-0 pt-5">
					<h3 class="card-title align-items-start flex-column">
						<span class="card-label fw-bold fs-4 text-gray-800">@lang('sale.shipping_details')</span>
					</h3>
				</div>
				<div class="card-body pt-0">
				<div class="row g-4">
			<div class="col-md-4">
				<div class="mb-5">
		            {!! Form::label('shipping_details', __('sale.shipping_details'), ['class' => 'form-label']) !!}
		            {!! Form::textarea('shipping_details', $transaction->shipping_details, ['class' => 'form-control form-control-solid', 'placeholder' => __('sale.shipping_details'), 'rows' => '3', 'cols'=>'30']); !!}
		        </div>
			</div>
			<div class="col-md-4">
				<div class="mb-5">
		            {!! Form::label('shipping_address', __('lang_v1.shipping_address'), ['class' => 'form-label']) !!}
		            {!! Form::textarea('shipping_address', $transaction->shipping_address, ['class' => 'form-control form-control-solid', 'placeholder' => __('lang_v1.shipping_address'), 'rows' => '3', 'cols'=>'30']); !!}
		        </div>
			</div>
			<div class="col-md-4">
				<div class="mb-5">
					{!! Form::label('shipping_charges', __('sale.shipping_charges'), ['class' => 'form-label']) !!}
					<div class="input-group input-group-solid">
					<span class="input-group-text">
					<i class="ki-duotone ki-dollar fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
					</span>
					{!! Form::text('shipping_charges', num_format_value($transaction->shipping_charges), ['class'=>'form-control form-control-solid input_number', 'placeholder'=> __('sale.shipping_charges')]); !!}
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="mb-5">
		            {!! Form::label('shipping_status', __('lang_v1.shipping_status'), ['class' => 'form-label']) !!}
		            {!! Form::select('shipping_status', $shipping_statuses, $transaction->shipping_status, ['class' => 'form-select form-select-solid', 'placeholder' => __('messages.please_select')]); !!}
		        </div>
			</div>
			<div class="col-md-4">
		        <div class="mb-5">
		            {!! Form::label('delivered_to', __('lang_v1.delivered_to') . ':', ['class' => 'form-label']) !!}
		            {!! Form::text('delivered_to', $transaction->delivered_to, ['class' => 'form-control form-control-solid', 'placeholder' => __('lang_v1.delivered_to')]); !!}
		        </div>
		    </div>
			<div class="col-md-4">
				<div class="mb-5">
					{!! Form::label('delivery_person', __('lang_v1.delivery_person') . ':', ['class' => 'form-label']) !!}
					{!! Form::select('delivery_person', $users, $transaction->delivery_person, ['class' => 'form-select form-select-solid select2', 'placeholder' => __('messages.please_select')]); !!}
				</div>
			</div>
		    @php
		        $shipping_custom_label_1 = !empty($custom_labels['shipping']['custom_field_1']) ? $custom_labels['shipping']['custom_field_1'] : '';

		        $is_shipping_custom_field_1_required = !empty($custom_labels['shipping']['is_custom_field_1_required']) && $custom_labels['shipping']['is_custom_field_1_required'] == 1 ? true : false;

		        $shipping_custom_label_2 = !empty($custom_labels['shipping']['custom_field_2']) ? $custom_labels['shipping']['custom_field_2'] : '';

		        $is_shipping_custom_field_2_required = !empty($custom_labels['shipping']['is_custom_field_2_required']) && $custom_labels['shipping']['is_custom_field_2_required'] == 1 ? true : false;

		        $shipping_custom_label_3 = !empty($custom_labels['shipping']['custom_field_3']) ? $custom_labels['shipping']['custom_field_3'] : '';
		        
		        $is_shipping_custom_field_3_required = !empty($custom_labels['shipping']['is_custom_field_3_required']) && $custom_labels['shipping']['is_custom_field_3_required'] == 1 ? true : false;

		        $shipping_custom_label_4 = !empty($custom_labels['shipping']['custom_field_4']) ? $custom_labels['shipping']['custom_field_4'] : '';
		        
		        $is_shipping_custom_field_4_required = !empty($custom_labels['shipping']['is_custom_field_4_required']) && $custom_labels['shipping']['is_custom_field_4_required'] == 1 ? true : false;

		        $shipping_custom_label_5 = !empty($custom_labels['shipping']['custom_field_5']) ? $custom_labels['shipping']['custom_field_5'] : '';
		        
		        $is_shipping_custom_field_5_required = !empty($custom_labels['shipping']['is_custom_field_5_required']) && $custom_labels['shipping']['is_custom_field_5_required'] == 1 ? true : false;
	        @endphp

	        @if(!empty($shipping_custom_label_1))
	        	@php
	        		$label_1 = $shipping_custom_label_1 . ':';
	        		if($is_shipping_custom_field_1_required) {
	        			$label_1 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="mb-5">
			            {!! Form::label('shipping_custom_field_1', $label_1, ['class' => 'form-label' . ($is_shipping_custom_field_1_required ? ' required' : '')]) !!}
			            {!! Form::text('shipping_custom_field_1', !empty($transaction->shipping_custom_field_1) ? $transaction->shipping_custom_field_1 : null, ['class' => 'form-control form-control-solid', 'placeholder' => $shipping_custom_label_1, 'required' => $is_shipping_custom_field_1_required]); !!}
			        </div>
			    </div>
	        @endif
	        @if(!empty($shipping_custom_label_2))
	        	@php
	        		$label_2 = $shipping_custom_label_2 . ':';
	        		if($is_shipping_custom_field_2_required) {
	        			$label_2 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="mb-5">
			            {!! Form::label('shipping_custom_field_2', $label_2, ['class' => 'form-label' . ($is_shipping_custom_field_2_required ? ' required' : '')]) !!}
			            {!! Form::text('shipping_custom_field_2', !empty($transaction->shipping_custom_field_2) ? $transaction->shipping_custom_field_2 : null, ['class' => 'form-control form-control-solid', 'placeholder' => $shipping_custom_label_2, 'required' => $is_shipping_custom_field_2_required]); !!}
			        </div>
			    </div>
	        @endif
	        @if(!empty($shipping_custom_label_3))
	        	@php
	        		$label_3 = $shipping_custom_label_3 . ':';
	        		if($is_shipping_custom_field_3_required) {
	        			$label_3 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="mb-5">
			            {!! Form::label('shipping_custom_field_3', $label_3, ['class' => 'form-label' . ($is_shipping_custom_field_3_required ? ' required' : '')]) !!}
			            {!! Form::text('shipping_custom_field_3', !empty($transaction->shipping_custom_field_3) ? $transaction->shipping_custom_field_3 : null, ['class' => 'form-control form-control-solid', 'placeholder' => $shipping_custom_label_3, 'required' => $is_shipping_custom_field_3_required]); !!}
			        </div>
			    </div>
	        @endif
	        @if(!empty($shipping_custom_label_4))
	        	@php
	        		$label_4 = $shipping_custom_label_4 . ':';
	        		if($is_shipping_custom_field_4_required) {
	        			$label_4 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="mb-5">
			            {!! Form::label('shipping_custom_field_4', $label_4, ['class' => 'form-label' . ($is_shipping_custom_field_4_required ? ' required' : '')]) !!}
			            {!! Form::text('shipping_custom_field_4', !empty($transaction->shipping_custom_field_4) ? $transaction->shipping_custom_field_4 : null, ['class' => 'form-control form-control-solid', 'placeholder' => $shipping_custom_label_4, 'required' => $is_shipping_custom_field_4_required]); !!}
			        </div>
			    </div>
	        @endif
	        @if(!empty($shipping_custom_label_5))
	        	@php
	        		$label_5 = $shipping_custom_label_5 . ':';
	        		if($is_shipping_custom_field_5_required) {
	        			$label_5 .= '*';
	        		}
	        	@endphp

	        	<div class="col-md-4">
			        <div class="mb-5">
			            {!! Form::label('shipping_custom_field_5', $label_5, ['class' => 'form-label' . ($is_shipping_custom_field_5_required ? ' required' : '')]) !!}
			            {!! Form::text('shipping_custom_field_5', !empty($transaction->shipping_custom_field_5) ? $transaction->shipping_custom_field_5 : null, ['class' => 'form-control form-control-solid', 'placeholder' => $shipping_custom_label_5, 'required' => $is_shipping_custom_field_5_required]); !!}
			        </div>
			    </div>
	        @endif
	        <div class="col-md-4">
                <div class="mb-5">
                    {!! Form::label('shipping_documents', __('lang_v1.shipping_documents') . ':', ['class' => 'form-label']) !!}
                    {!! Form::file('shipping_documents[]', ['id' => 'shipping_documents', 'class' => 'form-control form-control-solid', 'multiple', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                    <p class="text-muted fs-7 mb-0 mt-2">
                    	@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                    	@includeIf('components.document_help_text')
                    </p>
                    @php
                    	$medias = $transaction->media->where('model_media_type', 'shipping_document')->all();
                    @endphp
                    @include('sell.partials.media_table', ['medias' => $medias, 'delete' => true])
                </div>
            </div>
	        @php
	        	$_edit_has_ae = !empty($transaction->additional_expense_key_1) || !empty($transaction->additional_expense_key_2) || !empty($transaction->additional_expense_key_3) || !empty($transaction->additional_expense_key_4)
	        		|| abs((float) $transaction->additional_expense_value_1) > 0 || abs((float) $transaction->additional_expense_value_2) > 0
	        		|| abs((float) $transaction->additional_expense_value_3) > 0 || abs((float) $transaction->additional_expense_value_4) > 0;
	        @endphp
	        <div class="col-12 text-center">
				<button type="button" class="btn btn-light-primary" id="toggle_additional_expense"><i class="ki-duotone ki-plus fs-3"><span class="path1"></span><span class="path2"></span></i> @lang('lang_v1.add_additional_expenses') <i class="ki-duotone ki-down fs-5"><span class="path1"></span></i></button>
			</div>
			<div class="col-lg-8 ms-lg-auto" id="additional_expenses_div" @if(!$_edit_has_ae) style="display: none;" @endif>
				<table class="table table-row-bordered align-middle gy-3">
					<thead>
						<tr>
							<th>@lang('lang_v1.additional_expense_name')</th>
							<th>@lang('sale.amount')</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>{!! Form::text('additional_expense_key_1', $transaction->additional_expense_key_1, ['class' => 'form-control form-control-solid', 'id' => 'additional_expense_key_1']); !!}</td>
							<td>{!! Form::text('additional_expense_value_1', num_format_value($transaction->additional_expense_value_1), ['class' => 'form-control form-control-solid input_number', 'id' => 'additional_expense_value_1']); !!}</td>
						</tr>
						<tr>
							<td>{!! Form::text('additional_expense_key_2', $transaction->additional_expense_key_2, ['class' => 'form-control form-control-solid', 'id' => 'additional_expense_key_2']); !!}</td>
							<td>{!! Form::text('additional_expense_value_2', num_format_value($transaction->additional_expense_value_2), ['class' => 'form-control form-control-solid input_number', 'id' => 'additional_expense_value_2']); !!}</td>
						</tr>
						<tr>
							<td>{!! Form::text('additional_expense_key_3', $transaction->additional_expense_key_3, ['class' => 'form-control form-control-solid', 'id' => 'additional_expense_key_3']); !!}</td>
							<td>{!! Form::text('additional_expense_value_3', num_format_value($transaction->additional_expense_value_3), ['class' => 'form-control form-control-solid input_number', 'id' => 'additional_expense_value_3']); !!}</td>
						</tr>
						<tr>
							<td>{!! Form::text('additional_expense_key_4', $transaction->additional_expense_key_4, ['class' => 'form-control form-control-solid', 'id' => 'additional_expense_key_4']); !!}</td>
							<td>{!! Form::text('additional_expense_value_4', num_format_value($transaction->additional_expense_value_4), ['class' => 'form-control form-control-solid input_number', 'id' => 'additional_expense_value_4']); !!}</td>
						</tr>
					</tbody>
				</table>
			</div>
		    <div class="col-md-4 ms-md-auto text-md-end">
		    	@if(!empty($pos_settings['amount_rounding_method']) && $pos_settings['amount_rounding_method'] > 0)
		    	<div class="fs-7 text-muted mb-2" id="round_off">(@lang('lang_v1.round_off'): <span id="round_off_text">0</span>)</div>
				<input type="hidden" name="round_off_amount" id="round_off_amount" value="0">
				@endif
		    	<div class="fs-4 fw-bold text-gray-900">@lang('sale.total_payable'):
					<input type="hidden" name="final_total" id="final_total_input">
					<span id="total_payable" class="text-primary">0</span>
				</div>
		    </div>
				</div>
				</div>
			</div>
			@if(!empty($common_settings['is_enabled_export']) && $transaction->type != 'sales_order')
				<div class="card card-flush shadow-sm mb-5">
					<div class="card-header border-0 pt-5">
						<h3 class="card-title fw-bold fs-4 text-gray-800">@lang('lang_v1.export')</h3>
					</div>
					<div class="card-body pt-0">
					<div class="row g-4">
					<div class="col-12">
		                <div class="form-check form-check-custom form-check-solid">
		                    <input type="checkbox" name="is_export" class="form-check-input" id="is_export" @if(!empty($transaction->is_export)) checked @endif>
		                    <label class="form-check-label" for="is_export">@lang('lang_v1.is_export')</label>
		                </div>
		            </div>
			        @php $i = 1; @endphp
		            @for($i; $i <= 6 ; $i++)
		                <div class="col-md-4 export_div" @if(empty($transaction->is_export)) style="display: none;" @endif>
		                    <div class="mb-5">
		                        {!! Form::label('export_custom_field_'.$i, __('lang_v1.export_custom_field'.$i).':', ['class' => 'form-label']) !!}
		                        {!! Form::text('export_custom_fields_info['.'export_custom_field_'.$i.']', !empty($transaction->export_custom_fields_info['export_custom_field_'.$i]) ? $transaction->export_custom_fields_info['export_custom_field_'.$i] : null, ['class' => 'form-control form-control-solid', 'placeholder' => __('lang_v1.export_custom_field'.$i), 'id' => 'export_custom_field_'.$i]); !!}
		                    </div>
		                </div>
		            @endfor
					</div>
					</div>
				</div>
			@endif
	@php
		$is_enabled_download_pdf = config('constants.enable_download_pdf');
	@endphp
	@if($is_enabled_download_pdf && $transaction->type != 'sales_order')
		@can('sell.payments')
			<div class="card card-flush shadow-sm mb-5">
				<div class="card-header border-0 pt-5">
					<h3 class="card-title fw-bold fs-4 text-gray-800">@lang('purchase.add_payment')</h3>
				</div>
				<div class="card-body pt-0">
				<div class="row g-4 mb-0 p-4 bg-light rounded">
					<div class="col-md-6">
						<div class="mb-5">
							{!! Form::label("prefer_payment_method", __('lang_v1.prefer_payment_method') . ':', ['class' => 'form-label']) !!}
							@show_tooltip(__('lang_v1.this_will_be_shown_in_pdf'))
							<div class="input-group input-group-solid">
								<span class="input-group-text">
									<i class="ki-duotone ki-dollar fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
								</span>
								{!! Form::select("prefer_payment_method", $payment_types, $transaction->prefer_payment_method, ['class' => 'form-select form-select-solid', 'style' => 'width:100%;']); !!}
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="mb-5">
							{!! Form::label("prefer_payment_account", __('lang_v1.prefer_payment_account') . ':', ['class' => 'form-label']) !!}
							@show_tooltip(__('lang_v1.this_will_be_shown_in_pdf'))
							<div class="input-group input-group-solid">
								<span class="input-group-text">
									<i class="ki-duotone ki-bank fs-2"><span class="path1"></span><span class="path2"></span></i>
								</span>
								{!! Form::select("prefer_payment_account", $accounts, $transaction->prefer_payment_account, ['class' => 'form-select form-select-solid', 'style' => 'width:100%;']); !!}
							</div>
						</div>
					</div>
				</div>
				</div>
			</div>
		@endcan
	@endif

	@if($transaction->type == 'sell')
	@can('sell.payments')
		<div class="card card-flush shadow-sm mb-5">
			<div class="card-header border-0 pt-5">
				<h3 class="card-title fw-bold fs-4 text-gray-800">@lang('purchase.add_payment')</h3>
			</div>
			<div class="card-body pt-0">
		<div id="payment_rows_div" class="row g-4 @if($transaction->status != 'final') hide @endif">
			@foreach($payment_lines as $payment_line)
				@if($payment_line['is_return'] == 1)
					@php $change_return = $payment_line; @endphp
					@continue
				@endif
				@if(!empty($payment_line['id']))
        			{!! Form::hidden("payment[$loop->index][payment_id]", $payment_line['id']); !!}
        		@endif
				<div class="payment_row col-12">
					@include('sale_pos.partials.payment_row_form', ['row_index' => $loop->index, 'show_date' => true, 'payment_line' => $payment_line, 'show_denomination' => true])
				</div>
			@endforeach
		</div>
			<div class="row g-4">
			<div class="col-md-12">
        		<hr class="my-4">
        		<strong class="text-gray-800">@lang('lang_v1.change_return'):</strong>
        		<br>
        		<span class="lead fw-bold change_return_span">0</span>
        		{!! Form::hidden("change_return", $change_return['amount'], ['class' => 'form-control change_return input_number', 'required', 'id' => 'change_return']); !!}
        		@if(!empty($change_return['id']))
            		<input type="hidden" name="change_return_id" value="{{ $change_return['id'] }}">
            	@endif
			</div>
			</div>
		<div class="row g-4 @if($change_return['amount'] == 0) hide @endif payment_row" id="change_return_payment_data">
			<div class="col-md-4">
				<div class="mb-5">
					{!! Form::label("change_return_method", __('lang_v1.change_return_payment_method') . ':*', ['class' => 'form-label required']) !!}
					<div class="input-group input-group-solid">
						<span class="input-group-text">
							<i class="ki-duotone ki-dollar fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
						</span>
						@php
							$_payment_method = empty($change_return['method']) && array_key_exists('cash', $payment_types) ? 'cash' : $change_return['method'];
							$_payment_types = $payment_types;
							if (isset($_payment_types['advance'])) {
								unset($_payment_types['advance']);
							}
						@endphp
						{!! Form::select("payment[change_return][method]", $_payment_types, $_payment_method, ['class' => 'form-select form-select-solid payment_types_dropdown', 'id' => 'change_return_method', 'style' => 'width:100%;']); !!}
					</div>
				</div>
			</div>
			@if(!empty($accounts))
			<div class="col-md-4">
				<div class="mb-5">
					{!! Form::label("change_return_account", __('lang_v1.change_return_payment_account') . ':', ['class' => 'form-label']) !!}
					<div class="input-group input-group-solid">
						<span class="input-group-text">
							<i class="ki-duotone ki-bank fs-2"><span class="path1"></span><span class="path2"></span></i>
						</span>
						{!! Form::select("payment[change_return][account_id]", $accounts, !empty($change_return['account_id']) ? $change_return['account_id'] : '', ['class' => 'form-select form-select-solid select2', 'id' => 'change_return_account', 'style' => 'width:100%;']); !!}
					</div>
				</div>
			</div>
			@endif
			@include('sale_pos.partials.payment_type_details', ['payment_line' => $change_return, 'row_index' => 'change_return'])
		</div>
		<hr class="my-5">
		<div class="row">
			<div class="col-12 text-end">
				<strong class="text-gray-800">@lang('lang_v1.balance'):</strong> <span class="balance_due fs-5 fw-bold">0.00</span>
			</div>
		</div>
			</div>
		</div>
	@endcan
	@endif
		<div class="row g-4">
		<div class="col-12 text-center py-5">
	    	{!! Form::hidden('is_save_and_print', 0, ['id' => 'is_save_and_print']); !!}
	    	<button type="button" class="btn btn-primary btn-lg me-3" id="submit-sell">@lang('messages.update')</button>
	    	<button type="button" id="save-and-print" class="btn btn-success btn-lg">@lang('lang_v1.update_and_print')</button>
	    </div>
		</div>
		</div>
	</div>
	@if(in_array('subscription', $enabled_modules))
		@include('sale_pos.partials.recurring_invoice_modal')
	@endif
	{!! Form::close() !!}
</div>

<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

@include('sale_pos.partials.configure_search_modal')

@stop

@section('javascript')
	<script src="{{ asset('assets/app/js/pos.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('assets/app/js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('assets/app/js/opening_stock.js?v=' . $asset_v) }}"></script>
	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<script src="{{ asset('assets/app/js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <script type="text/javascript">
    	$(document).ready( function(){
    		$('#shipping_documents').fileinput({
		        showUpload: false,
		        showPreview: false,
		        browseLabel: LANG.file_browse_label,
		        removeLabel: LANG.remove,
		    });

		    $('#is_export').on('change', function () {
	            if ($(this).is(':checked')) {
	                $('div.export_div').show();
	            } else {
	                $('div.export_div').hide();
	            }
	        });

	        $('#status').change(function(){
    			if ($(this).val() == 'final') {
    				$('#payment_rows_div').removeClass('hide');
    			} else {
    				$('#payment_rows_div').addClass('hide');
    			}
    		});
    		@if(!empty($payment_types) && config('constants.enable_download_pdf') && $transaction->type != 'sales_order')
    		$(document).on('change', '#prefer_payment_method', function() {
			    var default_accounts = $('#location_id').data('default_payment_accounts');
			    var payment_type = $(this).val();
			    if (payment_type && default_accounts) {
			        var default_account = default_accounts[payment_type] && default_accounts[payment_type]['account'] ? default_accounts[payment_type]['account'] : '';
			        var account_dropdown = $('select#prefer_payment_account');
			        if (account_dropdown.length) {
			            account_dropdown.val(default_account).change();
			        }
			    }
			});
			@endif
    		$('.paid_on').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });
    	});
    </script>
@endsection

