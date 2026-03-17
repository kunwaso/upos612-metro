@extends('layouts.app')

@section('title', __('product.edit_order'))

@section('content')
<div class="d-none" data-projectx-transaction-id="{{ (int) $transaction->id }}"></div>
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('product.edit_order') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('product.invoice_no') }}: {{ $transaction->invoice_no }}</div>
    </div>
    <a href="{{ route('product.sales.orders.show', ['id' => $transaction->id]) }}" class="btn btn-light-primary btn-sm">
        <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ __('product.back_to_order') }}
    </a>
</div>

@if(session('status'))
    <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show mb-5" role="alert">
        {{ session('status.msg') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('product.close') }}"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-6">
        <div class="fw-bold mb-2">{{ __('product.validation_failed') }}</div>
        <ul class="mb-0 ps-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('product.sales.orders.update', ['id' => $transaction->id]) }}" id="projectx_sales_order_edit_form">
    @csrf
    @method('PUT')

    <input type="hidden" name="change_return" value="{{ old('change_return', 0) }}">
    <input type="hidden" name="change_return_id" value="{{ old('change_return_id', '') }}">
    <input type="hidden" name="is_save_and_print" value="0">
    <input type="hidden" name="final_total" id="projectx_final_total" value="{{ old('final_total', $finalTotalInputValue ?? '') }}">

    <div class="card card-flush mb-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('product.order_details') }}</h3>
        </div>
        <div class="card-body pt-5">
            <div class="row g-5">
                <div class="col-md-3">
                    <label class="form-label">{{ __('product.invoice_no') }}</label>
                    <input type="text" class="form-control form-control-solid" value="{{ $transaction->invoice_no }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label required">{{ __('product.sale_date') }}</label>
                    <input type="text" class="form-control form-control-solid" name="transaction_date" value="{{ old('transaction_date', $transactionDateFormatted ?? '') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label required">{{ __('product.status') }}</label>
                    <select class="form-select form-select-solid" name="status" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ (string) $statusValue === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('product.delivery_date') }}</label>
                    <input type="text" class="form-control form-control-solid" id="kt_projectx_orders_edit_delivery_date" name="delivery_date" value="{{ old('delivery_date', $deliveryDate ?? '') }}" placeholder="{{ __('product.delivery_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('product.pay_term_number') }}</label>
                    <input type="number" min="0" class="form-control form-control-solid" name="pay_term_number" value="{{ old('pay_term_number', $transaction->pay_term_number) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('product.pay_term_type') }}</label>
                    <select class="form-select form-select-solid" name="pay_term_type">
                        <option value="">{{ __('product.none') }}</option>
                        <option value="days" {{ old('pay_term_type', $transaction->pay_term_type) === 'days' ? 'selected' : '' }}>{{ __('product.days') }}</option>
                        <option value="months" {{ old('pay_term_type', $transaction->pay_term_type) === 'months' ? 'selected' : '' }}>{{ __('product.months') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label required">{{ __('product.customer') }}</label>
                    <select class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('product.customer') }}" name="contact_id" required>
                        <option value=""></option>
                        @foreach($contactsDropdown as $id => $label)
                            <option value="{{ $id }}" {{ (string) old('contact_id', $transaction->contact_id) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label required">{{ __('product.location') }}</label>
                    <select class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('product.location') }}" name="location_id" id="projectx_location_id" required>
                        <option value=""></option>
                        @foreach($locationsDropdown as $id => $label)
                            <option value="{{ $id }}" {{ (string) old('location_id', $transaction->location_id) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-6">
        <div class="card-header pt-7 d-flex justify-content-between align-items-center">
            <h3 class="card-title fw-bold text-gray-900">{{ __('product.sale_items') }}</h3>
            <button type="button" class="btn btn-light-primary btn-sm" id="projectx_add_line_btn">
                <i class="ki-duotone ki-plus fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('product.add_line') }}
            </button>
        </div>
        <div class="card-body pt-5">
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4" id="projectx_order_lines_table">
                    <thead>
                        <tr class="fw-bold text-muted text-uppercase fs-7">
                            <th class="min-w-250px">{{ __('product.product_name') }}</th>
                            <th class="min-w-120px">{{ __('product.quantity') }}</th>
                            <th class="min-w-150px">{{ __('product.unit_price_short') }}</th>
                            <th class="min-w-120px">{{ __('product.tax') }}</th>
                            <th class="min-w-120px">{{ __('product.subtotal') }}</th>
                            <th class="text-end min-w-100px">{{ __('product.action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="projectx_order_lines_body">
                        @foreach($initialProducts as $index => $line)
                            <tr class="projectx-order-line-row" data-index="{{ $index }}">
                                <td>
                                    <select class="form-select form-select-solid js-line-product-select" data-row-index="{{ $index }}" style="width: 100%;">
                                        @if(!empty($line['variation_id']) && !empty($line['product_id']))
                                            <option value="{{ $line['variation_id'] }}"
                                                data-product-id="{{ (int) $line['product_id'] }}"
                                                data-variation-id="{{ (int) $line['variation_id'] }}"
                                                data-product-name="{{ (string) ($line['product_name'] ?? '') }}"
                                                data-sub-sku="{{ (string) ($line['sub_sku'] ?? '') }}"
                                                data-product-type="{{ (string) ($line['product_type'] ?? 'single') }}"
                                                data-product-unit-id="{{ (int) ($line['product_unit_id'] ?? 0) }}"
                                                data-unit-price="{{ (string) ($line['unit_price_input'] ?? '0') }}"
                                                selected
                                            >{{ (string) ($line['option_label'] ?? '') }}</option>
                                        @endif
                                    </select>
                                    <div class="text-muted fs-8 mt-1 js-line-sku">{{ (string) ($line['sub_sku'] ?? '') }}</div>

                                    <input type="hidden" data-line-field="transaction_sell_lines_id" value="{{ $line['transaction_sell_lines_id'] ?? '' }}">
                                    <input type="hidden" data-line-field="product_id" class="js-field-product-id" value="{{ $line['product_id'] ?? '' }}">
                                    <input type="hidden" data-line-field="variation_id" class="js-field-variation-id" value="{{ $line['variation_id'] ?? '' }}">
                                    <input type="hidden" data-line-field="product_type" class="js-field-product-type" value="{{ $line['product_type'] ?? 'single' }}">
                                    <input type="hidden" data-line-field="product_unit_id" class="js-field-product-unit-id" value="{{ $line['product_unit_id'] ?? '' }}">
                                    <input type="hidden" data-line-field="base_unit_multiplier" class="js-field-base-unit-multiplier" value="{{ $line['base_unit_multiplier'] ?? 1 }}">
                                    <input type="hidden" data-line-field="tax_id" class="js-field-tax-id" value="{{ $line['tax_id'] ?? '' }}">
                                    <input type="hidden" data-line-field="item_tax" class="js-field-item-tax" value="{{ (string) ($line['item_tax_input'] ?? '0') }}">
                                    <input type="hidden" data-line-field="line_discount_type" class="js-field-line-discount-type" value="{{ $line['line_discount_type'] ?? 'fixed' }}">
                                    <input type="hidden" data-line-field="line_discount_amount" class="js-field-line-discount-amount" value="{{ (string) ($line['line_discount_amount_input'] ?? '0') }}">
                                    <input type="hidden" data-line-field="sell_line_note" class="js-field-sell-line-note" value="{{ $line['sell_line_note'] ?? '' }}">
                                    <input type="hidden" data-line-field="unit_price" class="js-field-unit-price-hidden" value="{{ (string) ($line['unit_price_hidden_input'] ?? '0') }}">
                                </td>
                                <td>
                                    <input type="number" min="{{ $projectxPositiveQuantityMin }}" step="{{ $projectxQuantityStep }}" class="form-control form-control-solid js-line-quantity" data-line-field="quantity" value="{{ (string) ($line['quantity_input'] ?? '0') }}" required>
                                </td>
                                <td>
                                    <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid js-line-unit-price" data-line-field="unit_price_inc_tax" value="{{ (string) ($line['unit_price_input'] ?? '0') }}" required>
                                </td>
                                <td>
                                    <span class="text-gray-700 fw-semibold js-line-tax-display">{{ (string) ($line['item_tax_display'] ?? '0') }}</span>
                                </td>
                                <td>
                                    <span class="text-gray-900 fw-bold js-line-total">0.00</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-icon btn-sm btn-light-danger js-remove-line">
                                        <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-danger fs-7 mt-3 d-none" id="projectx_line_error">{{ __('product.at_least_one_line_required') }}</div>
        </div>
    </div>

    <div class="row g-5 mb-6">
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header pt-7">
                    <h3 class="card-title fw-bold text-gray-900">{{ __('product.discount_tax_and_totals') }}</h3>
                </div>
                <div class="card-body pt-5">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <label class="form-label required">{{ __('product.discount_type') }}</label>
                            <select class="form-select form-select-solid" name="discount_type" id="projectx_discount_type" required>
                                <option value="fixed" {{ old('discount_type', $transaction->discount_type ?? 'fixed') === 'fixed' ? 'selected' : '' }}>{{ __('product.fixed') }}</option>
                                <option value="percentage" {{ old('discount_type', $transaction->discount_type ?? 'fixed') === 'percentage' ? 'selected' : '' }}>{{ __('product.percentage') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">{{ __('product.discount_amount') }}</label>
                            <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" name="discount_amount" id="projectx_discount_amount" value="{{ old('discount_amount', $discountAmountInputValue ?? '0') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('product.order_tax') }}</label>
                            <select class="form-select form-select-solid" name="tax_rate_id" id="projectx_tax_rate_id">
                                @foreach($taxRateOptions as $taxId => $taxLabel)
                                    <option value="{{ $taxId }}" data-rate="{{ data_get($taxRateAttributes, $taxId . '.data-rate', 0) }}" {{ (string) old('tax_rate_id', $transaction->tax_id) === (string) $taxId ? 'selected' : '' }}>{{ $taxLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="separator my-6"></div>
                    <div class="d-flex flex-column gap-2 text-end">
                        <div><span class="text-gray-600">{{ __('product.subtotal') }}:</span> <span class="fw-bold text-gray-900" id="projectx_subtotal_display">0.00</span></div>
                        <div><span class="text-gray-600">{{ __('product.discount') }}:</span> <span class="fw-bold text-gray-900" id="projectx_discount_display">0.00</span></div>
                        <div><span class="text-gray-600">{{ __('product.tax') }}:</span> <span class="fw-bold text-gray-900" id="projectx_tax_display">0.00</span></div>
                        <div><span class="text-gray-600">{{ __('product.shipping_charges') }}:</span> <span class="fw-bold text-gray-900" id="projectx_shipping_display">0.00</span></div>
                        <div><span class="text-gray-600">{{ __('product.additional_expense') }}:</span> <span class="fw-bold text-gray-900" id="projectx_expense_display">0.00</span></div>
                        <div class="fs-4"><span class="text-gray-800">{{ __('product.grand_total') }}:</span> <span class="fw-bolder text-gray-900" id="projectx_final_total_display">0.00</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header pt-7">
                    <h3 class="card-title fw-bold text-gray-900">{{ __('product.notes') }}</h3>
                </div>
                <div class="card-body pt-5">
                    <label class="form-label">{{ __('product.sale_note') }}</label>
                    <textarea class="form-control form-control-solid" name="sale_note" rows="10">{{ old('sale_note', $transaction->additional_notes) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-6">
        <div class="card-header pt-7 d-flex justify-content-between align-items-center">
            <h3 class="card-title fw-bold text-gray-900">{{ __('product.payment_info') }}</h3>
            <button type="button" class="btn btn-light-primary btn-sm" id="projectx_add_payment_btn">
                <i class="ki-duotone ki-plus fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('product.add_payment') }}
            </button>
        </div>
        <div class="card-body pt-5">
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted text-uppercase fs-7">
                            <th>{{ __('product.payment_method') }}</th>
                            <th>{{ __('product.amount') }}</th>
                            <th>{{ __('product.paid_on') }}</th>
                            <th>{{ __('product.note') }}</th>
                            <th class="text-end">{{ __('product.action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="projectx_payment_body">
                        @foreach($initialPayments as $index => $payment)
                            <tr class="projectx-payment-row" data-index="{{ $index }}">
                                <td>
                                    <select class="form-select form-select-solid" data-payment-field="method" required>
                                        @foreach($paymentTypes as $method => $label)
                                            <option value="{{ $method }}" {{ (string) ($payment['method'] ?? '') === (string) $method ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" data-payment-field="payment_id" value="{{ $payment['payment_id'] ?? '' }}">
                                    <input type="hidden" data-payment-field="account_id" value="{{ $payment['account_id'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" data-payment-field="amount" value="{{ (string) ($payment['amount_input'] ?? '0') }}" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-solid" data-payment-field="paid_on" value="{{ $payment['paid_on'] ?? '' }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-solid" data-payment-field="note" value="{{ $payment['note'] ?? '' }}">
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-icon btn-sm btn-light-danger js-remove-payment">
                                        <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('product.shipping_details') }}</h3>
        </div>
        <div class="card-body pt-5">
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label">{{ __('product.shipping_details') }}</label>
                    <textarea class="form-control form-control-solid" name="shipping_details" rows="3">{{ old('shipping_details', $transaction->shipping_details) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('product.shipping_address') }}</label>
                    <textarea class="form-control form-control-solid" name="shipping_address" rows="3">{{ old('shipping_address', $transaction->shipping_address) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.shipping_charges') }}</label>
                    <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid js-shipping-charge" name="shipping_charges" value="{{ old('shipping_charges', $shippingChargesInputValue ?? '0') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.shipping_status') }}</label>
                    <select class="form-select form-select-solid" name="shipping_status">
                        <option value="">{{ __('product.none') }}</option>
                        @foreach($shippingStatuses as $value => $label)
                            <option value="{{ $value }}" {{ (string) old('shipping_status', $transaction->shipping_status) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.shipping_custom_field_1') }}</label>
                    <input type="text" class="form-control form-control-solid" name="shipping_custom_field_1" value="{{ old('shipping_custom_field_1', $transaction->shipping_custom_field_1) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.shipping_custom_field_2') }}</label>
                    <input type="text" class="form-control form-control-solid" name="shipping_custom_field_2" value="{{ old('shipping_custom_field_2', $transaction->shipping_custom_field_2) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.shipping_custom_field_3') }}</label>
                    <input type="text" class="form-control form-control-solid" name="shipping_custom_field_3" value="{{ old('shipping_custom_field_3', $transaction->shipping_custom_field_3) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.shipping_custom_field_4') }}</label>
                    <input type="text" class="form-control form-control-solid" name="shipping_custom_field_4" value="{{ old('shipping_custom_field_4', $transaction->shipping_custom_field_4) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.shipping_custom_field_5') }}</label>
                    <input type="text" class="form-control form-control-solid" name="shipping_custom_field_5" value="{{ old('shipping_custom_field_5', $transaction->shipping_custom_field_5) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('product.additional_expense') }}</h3>
        </div>
        <div class="card-body pt-5">
            <div class="row g-5">
                @for($i = 1; $i <= 4; $i++)
                    <div class="col-md-4">
                        <label class="form-label">{{ __('product.expense_label') }} {{ $i }}</label>
                        <input type="text" class="form-control form-control-solid" name="additional_expense_key_{{ $i }}" value="{{ old('additional_expense_key_' . $i, data_get($transaction, 'additional_expense_key_' . $i)) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('product.amount') }}</label>
                        <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid js-expense-value" name="additional_expense_value_{{ $i }}" value="{{ old('additional_expense_value_' . $i, data_get($additionalExpenseInputValues ?? [], $i, '0')) }}">
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-3">
        <a href="{{ route('product.sales.orders.show', ['id' => $transaction->id]) }}" class="btn btn-light">{{ __('product.cancel') }}</a>
        <button type="submit" class="btn btn-primary" id="projectx_submit_btn">
            <i class="ki-duotone ki-check fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('product.update_order') }}
        </button>
    </div>
</form>

<script type="text/template" id="projectx_line_template">
<tr class="projectx-order-line-row" data-index="__INDEX__">
    <td>
        <select class="form-select form-select-solid js-line-product-select" data-row-index="__INDEX__" style="width: 100%;"></select>
        <div class="text-muted fs-8 mt-1 js-line-sku"></div>

        <input type="hidden" data-line-field="transaction_sell_lines_id" value="">
        <input type="hidden" data-line-field="product_id" class="js-field-product-id" value="">
        <input type="hidden" data-line-field="variation_id" class="js-field-variation-id" value="">
        <input type="hidden" data-line-field="product_type" class="js-field-product-type" value="single">
        <input type="hidden" data-line-field="product_unit_id" class="js-field-product-unit-id" value="">
        <input type="hidden" data-line-field="base_unit_multiplier" class="js-field-base-unit-multiplier" value="1">
        <input type="hidden" data-line-field="tax_id" class="js-field-tax-id" value="">
        <input type="hidden" data-line-field="item_tax" class="js-field-item-tax" value="0">
        <input type="hidden" data-line-field="line_discount_type" class="js-field-line-discount-type" value="fixed">
        <input type="hidden" data-line-field="line_discount_amount" class="js-field-line-discount-amount" value="0">
        <input type="hidden" data-line-field="sell_line_note" class="js-field-sell-line-note" value="">
        <input type="hidden" data-line-field="unit_price" class="js-field-unit-price-hidden" value="0">
    </td>
    <td>
        <input type="number" min="{{ $projectxPositiveQuantityMin }}" step="{{ $projectxQuantityStep }}" class="form-control form-control-solid js-line-quantity" data-line-field="quantity" value="{{ $projectxPositiveQuantityMin }}" required>
    </td>
    <td>
        <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid js-line-unit-price" data-line-field="unit_price_inc_tax" value="0" required>
    </td>
    <td>
        <span class="text-gray-700 fw-semibold js-line-tax-display">0.00</span>
    </td>
    <td>
        <span class="text-gray-900 fw-bold js-line-total">0.00</span>
    </td>
    <td class="text-end">
        <button type="button" class="btn btn-icon btn-sm btn-light-danger js-remove-line">
            <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
        </button>
    </td>
</tr>
</script>

<script type="text/template" id="projectx_payment_template">
<tr class="projectx-payment-row" data-index="__INDEX__">
    <td>
        <select class="form-select form-select-solid" data-payment-field="method" required>
            @foreach($paymentTypes as $method => $label)
                <option value="{{ $method }}" {{ $loop->first ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <input type="hidden" data-payment-field="payment_id" value="">
        <input type="hidden" data-payment-field="account_id" value="">
    </td>
    <td>
        <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" data-payment-field="amount" value="0" required>
    </td>
    <td>
        <input type="text" class="form-control form-control-solid" data-payment-field="paid_on" value="{{ now()->format('Y-m-d H:i:s') }}">
    </td>
    <td>
        <input type="text" class="form-control form-control-solid" data-payment-field="note" value="">
    </td>
    <td class="text-end">
        <button type="button" class="btn btn-icon btn-sm btn-light-danger js-remove-payment">
            <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
        </button>
    </td>
</tr>
</script>
@endsection

@section('page_javascript')
<script>
    (function () {
        const form = document.getElementById('projectx_sales_order_edit_form');
        if (!form) {
            return;
        }

        const lineBody = document.getElementById('projectx_order_lines_body');
        const paymentBody = document.getElementById('projectx_payment_body');
        const addLineBtn = document.getElementById('projectx_add_line_btn');
        const addPaymentBtn = document.getElementById('projectx_add_payment_btn');
        const locationInput = document.getElementById('projectx_location_id');
        const lineError = document.getElementById('projectx_line_error');

        const subtotalDisplay = document.getElementById('projectx_subtotal_display');
        const discountDisplay = document.getElementById('projectx_discount_display');
        const taxDisplay = document.getElementById('projectx_tax_display');
        const shippingDisplay = document.getElementById('projectx_shipping_display');
        const expenseDisplay = document.getElementById('projectx_expense_display');
        const finalTotalDisplay = document.getElementById('projectx_final_total_display');
        const finalTotalInput = document.getElementById('projectx_final_total');
        const deliveryDateInput = document.getElementById('kt_projectx_orders_edit_delivery_date');

        const productSearchUrl = '{{ route('product.sales.orders.product_search') }}';
        const currencySymbol = @json($currencySymbol);
        const currencyPrecision = @json((int) ($projectxCurrencyPrecision ?? 2));

        const initFlatpickr = (element, config) => {
            if (!element) {
                return;
            }

            if (typeof window.flatpickr === 'function') {
                window.flatpickr(element, config);
                return;
            }

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.flatpickr === 'function') {
                window.jQuery(element).flatpickr(config);
            }
        };

        initFlatpickr(deliveryDateInput, {
            altInput: true,
            altFormat: 'd M, Y',
            dateFormat: 'Y-m-d',
            allowInput: false
        });

        const parseNum = (value) => {
            const parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : 0;
        };

        const formatDecimal = (value, precision) => {
            return parseNum(value).toFixed(Math.max(0, precision));
        };

        const formatMoney = (value) => {
            return `${currencySymbol} ${formatDecimal(value, currencyPrecision)}`;
        };

        const buildLineFieldNames = () => {
            lineBody.querySelectorAll('.projectx-order-line-row').forEach((row, index) => {
                row.dataset.index = index;
                row.querySelectorAll('[data-line-field]').forEach((field) => {
                    field.name = `products[${index}][${field.dataset.lineField}]`;
                });
                const select = row.querySelector('.js-line-product-select');
                if (select) {
                    select.dataset.rowIndex = index;
                }
            });
        };

        const buildPaymentFieldNames = () => {
            paymentBody.querySelectorAll('.projectx-payment-row').forEach((row, index) => {
                row.dataset.index = index;
                row.querySelectorAll('[data-payment-field]').forEach((field) => {
                    field.name = `payment[${index}][${field.dataset.paymentField}]`;
                });
            });
        };

        const applySelectedProduct = (row, data) => {
            if (!row || !data) {
                return;
            }

            const productId = data.product_id || data.productId || '';
            const variationId = data.variation_id || data.variationId || data.id || '';
            const subSku = data.sub_sku || data.subSku || '';
            const productType = data.product_type || data.productType || 'single';
            const productUnitId = data.product_unit_id || data.productUnitId || '';
            const unitPrice = parseNum(data.unit_price_inc_tax || data.unit_price || 0);

            row.querySelector('.js-field-product-id').value = productId;
            row.querySelector('.js-field-variation-id').value = variationId;
            row.querySelector('.js-field-product-type').value = productType;
            row.querySelector('.js-field-product-unit-id').value = productUnitId;
            row.querySelector('.js-line-sku').textContent = subSku;

            const priceInput = row.querySelector('.js-line-unit-price');
            if (priceInput && parseNum(priceInput.value) <= 0) {
                priceInput.value = formatDecimal(unitPrice, currencyPrecision);
            }

            recalculateRow(row);
            recalculateTotals();
        };

        const initProductSelect = (selectEl) => {
            if (!selectEl || typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 === 'undefined') {
                return;
            }

            const $select = window.jQuery(selectEl);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                width: '100%',
                placeholder: '{{ __('product.search_products') }}',
                minimumInputLength: 1,
                ajax: {
                    url: productSearchUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: params.term || '',
                            page: params.page || 1,
                            location_id: locationInput ? locationInput.value : ''
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data && data.results ? data.results : [],
                            pagination: {
                                more: !!(data && data.pagination && data.pagination.more)
                            }
                        };
                    }
                }
            });

            $select.off('select2:select').on('select2:select', function (event) {
                const row = selectEl.closest('.projectx-order-line-row');
                applySelectedProduct(row, event.params && event.params.data ? event.params.data : null);
            });

            if ($select.find('option:selected').length) {
                const option = $select.find('option:selected')[0];
                if (option) {
                    applySelectedProduct(selectEl.closest('.projectx-order-line-row'), {
                        id: option.value,
                        product_id: option.dataset.productId,
                        variation_id: option.dataset.variationId,
                        sub_sku: option.dataset.subSku,
                        product_type: option.dataset.productType,
                        product_unit_id: option.dataset.productUnitId,
                        unit_price_inc_tax: option.dataset.unitPrice
                    });
                }
            }
        };

        const recalculateRow = (row) => {
            const quantity = parseNum(row.querySelector('.js-line-quantity')?.value);
            const unitPrice = parseNum(row.querySelector('.js-line-unit-price')?.value);
            const taxValue = parseNum(row.querySelector('.js-field-item-tax')?.value);
            const lineTotal = quantity * unitPrice;

            const unitPriceHidden = row.querySelector('.js-field-unit-price-hidden');
            if (unitPriceHidden) {
                const unitPriceExTax = Math.max(0, unitPrice - taxValue);
                unitPriceHidden.value = formatDecimal(unitPriceExTax, currencyPrecision);
            }

            const totalEl = row.querySelector('.js-line-total');
            if (totalEl) {
                totalEl.textContent = formatMoney(lineTotal);
                totalEl.dataset.value = formatDecimal(lineTotal, currencyPrecision);
            }

            const taxEl = row.querySelector('.js-line-tax-display');
            if (taxEl) {
                taxEl.textContent = formatDecimal(taxValue, currencyPrecision);
            }
        };

        const recalculateTotals = () => {
            let subtotal = 0;
            lineBody.querySelectorAll('.projectx-order-line-row').forEach((row) => {
                recalculateRow(row);
                subtotal += parseNum(row.querySelector('.js-line-total')?.dataset.value);
            });

            const discountType = document.getElementById('projectx_discount_type')?.value || 'fixed';
            const discountAmountInput = document.getElementById('projectx_discount_amount');
            const discountAmount = parseNum(discountAmountInput ? discountAmountInput.value : 0);

            let discountValue = discountAmount;
            if (discountType === 'percentage') {
                discountValue = (subtotal * discountAmount) / 100;
            }
            if (discountValue > subtotal) {
                discountValue = subtotal;
            }

            const taxable = Math.max(0, subtotal - discountValue);
            const taxSelect = document.getElementById('projectx_tax_rate_id');
            const selectedOption = taxSelect ? taxSelect.options[taxSelect.selectedIndex] : null;
            const taxRate = selectedOption ? parseNum(selectedOption.dataset.rate) : 0;
            const taxAmount = (taxable * taxRate) / 100;

            const shippingCharge = parseNum(document.querySelector('.js-shipping-charge')?.value);

            let additionalExpenses = 0;
            document.querySelectorAll('.js-expense-value').forEach((input) => {
                additionalExpenses += parseNum(input.value);
            });

            const finalTotal = taxable + taxAmount + shippingCharge + additionalExpenses;

            subtotalDisplay.textContent = formatMoney(subtotal);
            discountDisplay.textContent = formatMoney(discountValue);
            taxDisplay.textContent = formatMoney(taxAmount);
            shippingDisplay.textContent = formatMoney(shippingCharge);
            expenseDisplay.textContent = formatMoney(additionalExpenses);
            finalTotalDisplay.textContent = formatMoney(finalTotal);

            if (finalTotalInput) {
                finalTotalInput.value = formatDecimal(finalTotal, currencyPrecision);
            }
        };

        const ensureOneLineRow = () => {
            const rowCount = lineBody.querySelectorAll('.projectx-order-line-row').length;
            if (rowCount > 0) {
                lineError.classList.add('d-none');
                return true;
            }

            lineError.classList.remove('d-none');
            return false;
        };

        const ensureOnePaymentRow = () => {
            const rows = paymentBody.querySelectorAll('.projectx-payment-row');
            if (rows.length) {
                return;
            }

            const template = document.getElementById('projectx_payment_template');
            if (!template) {
                return;
            }

            const markup = template.innerHTML.replace(/__INDEX__/g, '0');
            paymentBody.insertAdjacentHTML('beforeend', markup);
            buildPaymentFieldNames();
        };

        addLineBtn.addEventListener('click', function () {
            const template = document.getElementById('projectx_line_template');
            if (!template) {
                return;
            }

            const nextIndex = lineBody.querySelectorAll('.projectx-order-line-row').length;
            const markup = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
            lineBody.insertAdjacentHTML('beforeend', markup);

            const newRow = lineBody.querySelector('.projectx-order-line-row:last-child');
            if (newRow) {
                initProductSelect(newRow.querySelector('.js-line-product-select'));
            }

            buildLineFieldNames();
            ensureOneLineRow();
            recalculateTotals();
        });

        addPaymentBtn.addEventListener('click', function () {
            const template = document.getElementById('projectx_payment_template');
            if (!template) {
                return;
            }

            const nextIndex = paymentBody.querySelectorAll('.projectx-payment-row').length;
            const markup = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
            paymentBody.insertAdjacentHTML('beforeend', markup);
            buildPaymentFieldNames();
        });

        lineBody.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('.js-remove-line');
            if (!removeBtn) {
                return;
            }

            const rows = lineBody.querySelectorAll('.projectx-order-line-row');
            if (rows.length <= 1) {
                lineError.classList.remove('d-none');
                return;
            }

            const row = removeBtn.closest('.projectx-order-line-row');
            if (row) {
                row.remove();
                buildLineFieldNames();
                ensureOneLineRow();
                recalculateTotals();
            }
        });

        lineBody.addEventListener('input', function (event) {
            if (!event.target.closest('.projectx-order-line-row')) {
                return;
            }

            if (event.target.classList.contains('js-line-quantity') || event.target.classList.contains('js-line-unit-price')) {
                recalculateTotals();
            }
        });

        paymentBody.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('.js-remove-payment');
            if (!removeBtn) {
                return;
            }

            const row = removeBtn.closest('.projectx-payment-row');
            if (row) {
                row.remove();
                buildPaymentFieldNames();
                ensureOnePaymentRow();
            }
        });

        document.getElementById('projectx_discount_type')?.addEventListener('change', recalculateTotals);
        document.getElementById('projectx_discount_amount')?.addEventListener('input', recalculateTotals);
        document.getElementById('projectx_tax_rate_id')?.addEventListener('change', recalculateTotals);
        document.querySelector('.js-shipping-charge')?.addEventListener('input', recalculateTotals);
        document.querySelectorAll('.js-expense-value').forEach((input) => {
            input.addEventListener('input', recalculateTotals);
        });

        locationInput?.addEventListener('change', function () {
            lineBody.querySelectorAll('.js-line-product-select').forEach((selectEl) => {
                if (window.jQuery && window.jQuery(selectEl).hasClass('select2-hidden-accessible')) {
                    window.jQuery(selectEl).val(null).trigger('change');
                }

                const row = selectEl.closest('.projectx-order-line-row');
                if (!row) {
                    return;
                }

                row.querySelector('.js-field-product-id').value = '';
                row.querySelector('.js-field-variation-id').value = '';
                row.querySelector('.js-line-sku').textContent = '';
            });

            recalculateTotals();
        });

        form.addEventListener('submit', function (event) {
            buildLineFieldNames();
            buildPaymentFieldNames();

            const hasLine = ensureOneLineRow();
            if (!hasLine) {
                event.preventDefault();
                return;
            }

            let hasEmptyProduct = false;
            lineBody.querySelectorAll('.projectx-order-line-row').forEach((row) => {
                const productId = row.querySelector('.js-field-product-id')?.value;
                const variationId = row.querySelector('.js-field-variation-id')?.value;
                if (!productId || !variationId) {
                    hasEmptyProduct = true;
                }
            });

            if (hasEmptyProduct) {
                event.preventDefault();
                lineError.classList.remove('d-none');
            }
        });

        buildLineFieldNames();
        buildPaymentFieldNames();

        lineBody.querySelectorAll('.js-line-product-select').forEach((selectEl) => {
            initProductSelect(selectEl);
        });

        ensureOneLineRow();
        ensureOnePaymentRow();
        recalculateTotals();
    })();
</script>
@endsection


