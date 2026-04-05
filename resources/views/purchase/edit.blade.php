@extends('layouts.app')
@section('title', __('purchase.edit_purchase'))

@section('content')
{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">
                @lang('purchase.edit_purchase')
                <i class="fa fa-keyboard-o hover-q text-muted ms-2 fs-5" aria-hidden="true"
                    data-container="body" data-toggle="popover" data-placement="bottom"
                    data-content="@include('purchase.partials.keyboard_shortcuts_details')"
                    data-html="true" data-trigger="hover"></i>
            </h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a>
                </li>
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ action([\App\Http\Controllers\PurchaseController::class, 'index']) }}" class="text-gray-600 text-hover-primary">@lang('purchase.purchase')</a>
                </li>
                <li class="breadcrumb-item text-gray-900">@lang('purchase.edit_purchase')</li>
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

  {!! Form::open(['url' =>  action([\App\Http\Controllers\PurchaseController::class, 'update'] , [$purchase->id] ), 'method' => 'PUT', 'id' => 'add_purchase_form', 'files' => true ]) !!}
  <div class="form d-flex flex-column flex-lg-row">
    <div class="w-100 flex-lg-row-auto w-lg-300px mb-7 me-7 me-lg-10">

  <input type="hidden" id="purchase_id" value="{{ $purchase->id }}">

    @component('components.widget', ['class' => 'mb-7', 'title' => __('product.order_details')])
        <div class="row g-5">
            <div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
              <div class="form-group">
                {!! Form::label('supplier_id', __('purchase.supplier') . ':*') !!}
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fa fa-user"></i>
                  </span>
                  {!! Form::select('contact_id', [ $purchase->contact_id => $purchase->contact->name], $purchase->contact_id, ['class' => 'form-select form-select-solid', 'placeholder' => __('messages.please_select') , 'required', 'id' => 'supplier_id']); !!}
                  <span class="input-group-btn">
                    <button type="button" class="btn btn-default bg-white btn-flat add_new_supplier" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
                </div>
              </div>
              <strong>
                @lang('business.address'):
              </strong>
              <div id="supplier_address_div">
                {!! $purchase->contact->contact_address !!}
              </div>
            </div>

            <div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
              <div class="form-group">
                {!! Form::label('ref_no', __('purchase.ref_no') . '*') !!}
                @show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
                {!! Form::text('ref_no', $purchase->ref_no, ['class' => 'form-control', 'required']); !!}
              </div>
            </div>
            
            <div class="@if(!empty($default_purchase_status)) col-sm-4 @else col-sm-3 @endif">
              <div class="form-group">
                {!! Form::label('transaction_date', __('purchase.purchase_date') . ':*') !!}
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fa fa-calendar"></i>
                  </span>
                  {!! Form::text('transaction_date', format_datetime_value($purchase->transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
                </div>
              </div>
            </div>
            
            <div class="col-sm-3 @if(!empty($default_purchase_status)) hide @endif">
              <div class="form-group">
                {!! Form::label('status', __('purchase.purchase_status') . ':*') !!}
                @show_tooltip(__('tooltip.order_status'))
                {!! Form::select('status', $orderStatuses, $purchase->status, ['class' => 'form-select form-select-solid select2', 'placeholder' => __('messages.please_select') , 'required']); !!}
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                @show_tooltip(__('tooltip.purchase_location'))
                {!! Form::select('location_id', $business_locations, $purchase->location_id, ['class' => 'form-select form-select-solid select2', 'placeholder' => __('messages.please_select'), 'disabled']); !!}
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
                  {!! Form::number('exchange_rate', $purchase->exchange_rate, ['class' => 'form-control', 'required', 'step' => 0.001]); !!}
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
                    {!! Form::number('pay_term_number', $purchase->pay_term_number, ['class' => 'form-control width-40 pull-left', 'min' => 0, 'placeholder' => __('contact.pay_term')]); !!}

                    {!! Form::select('pay_term_type', 
                      ['months' => __('lang_v1.months'), 
                        'days' => __('lang_v1.days')], 
                        $purchase->pay_term_type, 
                      ['class' => 'form-control width-60 pull-left','placeholder' => __('messages.please_select'), 'id' => 'pay_term_type']); !!}
                  </div>
              </div>
          </div>

            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                    {!! Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                    <p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                    @includeIf('components.document_help_text')</p>
                </div>
            </div>
        </div>
        <div class="row g-5">
          @foreach($purchase_custom_fields as $custom_field)
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label($custom_field['input_name'], $custom_field['display_label']) !!}
                    {!! Form::text(
                        $custom_field['input_name'],
                        $custom_field['value'],
                        ['class' => 'form-control', 'placeholder' => $custom_field['placeholder'], 'required' => $custom_field['required']]
                    ) !!}
                </div>
            </div>
          @endforeach
        </div>
        @if(!empty($common_settings['enable_purchase_order']))
        <div class="row g-5">
          <div class="col-sm-3">
            <div class="form-group">
              {!! Form::label('purchase_order_ids', __('lang_v1.purchase_order').':') !!}
              {!! Form::select('purchase_order_ids[]', $purchase_orders, $purchase->purchase_order_ids, ['class' => 'form-select form-select-solid select2', 'multiple', 'id' => 'purchase_order_ids']); !!}
            </div>
          </div>
        </div>
        @endif
    @endcomponent
    </div>
    <div class="d-flex flex-column flex-lg-row-fluid gap-7 gap-lg-10">

    @component('components.widget', ['class' => 'mb-7', 'title' => __('product.sale_items')])
        <div class="row g-5">
            <div class="col-sm-2 text-center">
              <button type="button" class="btn btn-light-primary btn-sm" data-toggle="modal" data-target="#import_purchase_products_modal">@lang('product.import_products')</button>
            </div>
            <div class="col-sm-8">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fa fa-search"></i>
                  </span>
                  {!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
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
              @include('purchase.partials.edit_purchase_entry_row', [
                'row_models' => $row_models,
                'next_row_count' => $next_row_count,
                'ui_flags' => $ui_flags,
              ])

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
                      <span id="total_subtotal" class="display_currency">{{$purchase->total_before_tax/$purchase->exchange_rate}}</span>
                      <!-- This is total before purchase tax-->
                      <input type="hidden" id="total_subtotal_input" value="{{$purchase->total_before_tax/$purchase->exchange_rate}}" name="total_before_tax">
                    </td>
                  </tr>
                </table>
              </div>

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
                        {!! Form::select('discount_type', [ '' => __('lang_v1.none'), 'fixed' => __( 'lang_v1.fixed' ), 'percentage' => __( 'lang_v1.percentage' )], $purchase->discount_type, ['class' => 'form-select form-select-solid select2', 'placeholder' => __('messages.please_select')]); !!}
                      </div>
                    </td>
                    <td class="col-md-3">
                      <div class="form-group">
                      {!! Form::label('discount_amount', __( 'purchase.discount_amount' ) . ':') !!}
                      {!! Form::text('discount_amount', 

                      ($purchase->discount_type == 'fixed' ? 
                        number_format($purchase->discount_amount/$purchase->exchange_rate, $ui_flags['currency_precision'], $currency_details->decimal_separator, $currency_details->thousand_separator)
                      :
                        number_format($purchase->discount_amount, $ui_flags['currency_precision'], $currency_details->decimal_separator, $currency_details->thousand_separator)
                      )
                      , ['class' => 'form-control input_number']); !!}
                      </div>
                    </td>
                    <td class="col-md-3">
                      &nbsp;
                    </td>
                    <td class="col-md-3">
                      <b>Discount:</b>(-) 
                      <span id="discount_calculated_amount" class="display_currency">0</span>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <div class="form-group">
                      {!! Form::label('tax_id', __( 'purchase.purchase_tax' ) . ':') !!}
                      <select name="tax_id" id="tax_id" class="form-select form-select-solid select2" placeholder="'Please Select'">
                        <option value="" data-tax_amount="0" selected>@lang('lang_v1.none')</option>
                        @foreach($taxes as $tax)
                          <option value="{{ $tax->id }}" @if($purchase->tax_id == $tax->id) {{'selected'}} @endif data-tax_amount="{{ $tax->amount }}"
                          >
                            {{ $tax->name }}
                          </option>
                        @endforeach
                      </select>
                      {!! Form::hidden('tax_amount', $purchase->tax_amount, ['id' => 'tax_amount']); !!}
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
                        {!! Form::textarea('additional_notes', $purchase->additional_notes, ['class' => 'form-control', 'rows' => 3]); !!}
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
        {!! Form::text('shipping_details', $purchase->shipping_details, ['class' => 'form-control']); !!}
        </div>
      </div>
      <div class="col-md-4 offset-md-4">
        <div class="form-group">
          {!! Form::label('shipping_charges','(+) ' . __( 'purchase.additional_shipping_charges') . ':') !!}
          {!! Form::text('shipping_charges', number_format($purchase->shipping_charges/$purchase->exchange_rate, $ui_flags['currency_precision'], $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number']); !!}
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
      <div class="col-md-8 offset-md-4" id="additional_expenses_div">
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
                {!! Form::text('additional_expense_key_1', $purchase->additional_expense_key_1, ['class' => 'form-control', 'id' => 'additional_expense_key_1']); !!}
              </td>
              <td>
                {!! Form::text('additional_expense_value_1', number_format($purchase->additional_expense_value_1/$purchase->exchange_rate, $ui_flags['currency_precision'], $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number', 'id' => 'additional_expense_value_1']); !!}
              </td>
            </tr>
            <tr>
              <td>
                {!! Form::text('additional_expense_key_2', $purchase->additional_expense_key_2, ['class' => 'form-control', 'id' => 'additional_expense_key_2']); !!}
              </td>
              <td>
                {!! Form::text('additional_expense_value_2', number_format($purchase->additional_expense_value_2/$purchase->exchange_rate, $ui_flags['currency_precision'], $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number', 'id' => 'additional_expense_value_2']); !!}
              </td>
            </tr>
            <tr>
              <td>
                {!! Form::text('additional_expense_key_3', $purchase->additional_expense_key_3, ['class' => 'form-control', 'id' => 'additional_expense_key_3']); !!}
              </td>
              <td>
                {!! Form::text('additional_expense_value_3', number_format($purchase->additional_expense_value_3/$purchase->exchange_rate, $ui_flags['currency_precision'], $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number', 'id' => 'additional_expense_value_3']); !!}
              </td>
            </tr>
            <tr>
              <td>
                {!! Form::text('additional_expense_key_4', $purchase->additional_expense_key_4, ['class' => 'form-control', 'id' => 'additional_expense_key_4']); !!}
              </td>
              <td>
                {!! Form::text('additional_expense_value_4', number_format($purchase->additional_expense_value_4/$purchase->exchange_rate, $ui_flags['currency_precision'], $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number', 'id' => 'additional_expense_value_4']); !!}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="row g-5">
    <div class="col-md-12 text-right">
      {!! Form::hidden('final_total', $purchase->final_total , ['id' => 'grand_total_hidden']); !!}
      <b>@lang('purchase.purchase_total'): </b><span id="grand_total" class="display_currency" data-currency_symbol='true'>{{$purchase->final_total}}</span>
    </div>
    </div>
    @endcomponent
  
    <div class="row g-5">
        <div class="col-sm-12 text-center">
          <button type="button" id="submit_purchase_form" class="btn btn-primary btn-lg">@lang('messages.update')</button>
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
@include('purchase.partials.import_purchase_products_modal')
@endsection

@section('javascript')
  <script src="{{ asset('assets/app/js/purchase.js?v=' . $asset_v) }}"></script>
  <script src="{{ asset('assets/app/js/product.js?v=' . $asset_v) }}"></script>
  <script type="text/javascript">
    $(document).ready( function(){
      update_table_total();
      update_grand_total();
      __page_leave_confirmation('#add_purchase_form');
    });
  </script>
  @include('purchase.partials.keyboard_shortcuts')
@endsection

