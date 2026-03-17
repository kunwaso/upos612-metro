@extends('layouts.app')

@section('title', __('product.edit_quote'))

@section('content')
<div class="d-none" data-projectx-quote-id="{{ (int) $quote->id }}"></div>
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('product.edit_quote') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('product.quote_no') }}: {{ $quote->quote_number ?: $quote->uuid }}</div>
    </div>
    <a href="{{ route('product.quotes.show', ['id' => $quote->id]) }}" class="btn btn-light-primary btn-sm">
        <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ __('product.view_quote') }}
    </a>
</div>

<form method="POST" action="{{ route('product.quotes.update', ['id' => $quote->id]) }}" id="projectx_quote_edit_form">
    @csrf
    @method('PATCH')

    <div class="card card-flush mb-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('product.quote_header') }}</h3>
        </div>
        <div class="card-body pt-5">
            <div class="row g-5 projectx-quote-header-selects">
                <div class="col-md-4">
                    <label class="form-label required" for="quote_edit_contact_id">{{ __('product.customer') }}</label>
                    <select class="form-select form-select-solid select2 projectx-solid-select2" name="contact_id" id="quote_edit_contact_id" data-control="select2" data-placeholder="{{ __('product.customer') }}" data-allow-clear="true">
                        <option value="">{{ __('product.customer') }}</option>
                        @foreach($customersDropdown as $id => $label)
                            <option value="{{ $id }}" {{ (string) old('contact_id', $quote->contact_id) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label required" for="quote_edit_location_id">{{ __('product.location') }}</label>
                    <select class="form-select form-select-solid select2 projectx-solid-select2" name="location_id" id="quote_edit_location_id" data-control="select2" data-placeholder="{{ __('product.location') }}" data-allow-clear="true">
                        <option value="">{{ __('product.location') }}</option>
                        @foreach($locationsDropdown as $id => $label)
                            <option value="{{ $id }}" {{ (string) old('location_id', $quote->location_id) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.customer_email') }}</label>
                    <input type="email" class="form-control form-control-solid" name="customer_email" value="{{ old('customer_email', $quote->customer_email) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.customer_name') }}</label>
                    <input type="text" class="form-control form-control-solid" name="customer_name" value="{{ old('customer_name', $quote->customer_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.quote_date') }}</label>
                    <input type="text" class="form-control form-control-solid" id="kt_projectx_quote_edit_quote_date" name="quote_date" value="{{ old('quote_date', optional($quote->quote_date)->format('Y-m-d') ?? $quote->created_at->format('Y-m-d')) }}" placeholder="{{ __('product.quote_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label required">{{ __('product.valid_until') }}</label>
                    <input type="text" class="form-control form-control-solid" id="kt_projectx_quote_edit_expires_at" name="expires_at" value="{{ old('expires_at', optional($quote->expires_at)->format('Y-m-d')) }}" placeholder="{{ __('product.valid_until') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('product.shipment_port') }}</label>
                    <input type="text" class="form-control form-control-solid" id="quote_edit_shipment_port" name="shipment_port" list="shipment_port_list" value="{{ old('shipment_port', $quote->shipment_port) }}" placeholder="{{ __('product.shipment_port') }}">
                    <datalist id="shipment_port_list">
                        @foreach(config('product.shipment_ports', []) as $port)
                            <option value="{{ $port }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('product.remark') }}</label>
                    <textarea class="form-control form-control-solid" name="remark" rows="6" placeholder="{{ __('product.remark') }}">{{ old('remark', $quote->remark ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-6">
        <div class="card-header pt-7 d-flex justify-content-between">
            <h3 class="card-title fw-bold text-gray-900">{{ __('product.quote_lines') }}</h3>
            <button type="button" class="btn btn-light-primary btn-sm" id="add_quote_line_btn">
                <i class="ki-duotone ki-plus fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('product.add_line') }}
            </button>
        </div>
        <div class="card-body pt-5">
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4" id="quote_lines_table">
                    <thead>
                        <tr class="fw-bold text-muted fs-7 text-uppercase">
                            <th>{{ __('product.category') }}</th>
                            <th>{{ __('product.quote_line_item') }}</th>
                            <th>{{ __('product.quantity') }}</th>
                            <th>{{ __('product.purchase_uom') }}</th>
                            <th>{{ __('product.base_mill_price') }}</th>
                            <th>{{ __('product.currency_label') }}</th>
                            <th>{{ __('product.incoterm') }}</th>
                            <th>{{ __('product.total') }}</th>
                            <th class="text-end">{{ __('product.action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="quote_lines_body">
                        @foreach($quoteLines as $index => $line)
                            <tr class="quote-line-row" data-index="{{ $index }}">
                                <td>
                                    <span class="text-gray-700 category-name">{{ $line['category'] ?? '' }}</span>
                                    <input type="hidden" data-field="line_type" name="lines[{{ $index }}][line_type]" value="fabric">
                                </td>
                                <td>
                                    <select class="form-select form-select-solid form-select-sm" data-field="id" name="lines[{{ $index }}][id]">
                                        <option value="">{{ __('product.select_fabric') }}</option>
                                        @foreach($fabrics as $product)
                                            <option
                                                value="{{ $product->id }}"
                                                data-base-price="{{ (float) ($product->price_500_yds ?? $product->selling_price ?? 0) }}"
                                                data-unit="{{ optional($product->unit)->short_name ?? '' }}"
                                                data-category="{{ optional($product->category)->name ?? '' }}"
                                                {{ (string) ($line['id'] ?? '') === (string) $product->id ? 'selected' : '' }}
                                            >
                                                {{ $product->name }}@if($product->fabric_sku ?? $product->sku) ({{ $product->fabric_sku ?? $product->sku }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" min="{{ $projectxPositiveQuantityMin }}" step="{{ $projectxQuantityStep }}" class="form-control form-control-solid form-control-sm" data-field="qty" name="lines[{{ $index }}][qty]" value="{{ $line['qty'] ?? 1 }}"></td>
                                <td>
                                    <input type="text" class="form-control form-control-solid form-control-sm" data-role="purchase-uom-display" value="{{ $line['purchase_uom'] ?? '' }}" readonly>
                                    <input type="hidden" data-field="purchase_uom" name="lines[{{ $index }}][purchase_uom]" value="{{ $line['purchase_uom'] ?? '' }}">
                                </td>
                                <td><input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid form-control-sm" data-field="base_mill_price" name="lines[{{ $index }}][base_mill_price]" value="{{ $line['base_mill_price'] ?? 0 }}"></td>
                                <td>
                                    <select class="form-select form-select-solid form-select-sm" data-field="currency" name="lines[{{ $index }}][currency]">
                                        @foreach($costingDropdowns['currency'] as $code => $label)
                                            <option value="{{ $code }}" {{ (string) ($line['currency'] ?? '') === (string) $code ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-solid form-select-sm" data-field="incoterm" name="lines[{{ $index }}][incoterm]">
                                        <option value="" {{ (string) ($line['incoterm'] ?? '') === '' ? 'selected' : '' }}></option>
                                        @foreach($costingDropdowns['incoterm'] as $option)
                                            <option value="{{ $option }}" {{ (string) ($line['incoterm'] ?? '') === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><span class="text-gray-900 fw-bold line-total">0.00</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-icon btn-sm btn-light-danger remove-line-btn">
                                        <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-5">
                <div class="text-end">
                    <div class="text-muted fs-7">{{ __('product.grand_total') }}</div>
                    <div class="fw-bolder fs-2 text-gray-900" id="quote_grand_total">0.00</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i class="ki-duotone ki-check fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('product.update_quote') }}
        </button>
    </div>
</form>

<table class="d-none">
    <tbody>
        <tr id="quote_line_template" class="quote-line-row" data-index="__INDEX__">
            <td>
                <span class="text-gray-700 category-name"></span>
                <input type="hidden" data-field="line_type" name="lines[__INDEX__][line_type]" value="fabric">
            </td>
            <td>
                <select class="form-select form-select-solid form-select-sm" data-field="id" name="lines[__INDEX__][id]">
                    <option value="">{{ __('product.select_fabric') }}</option>
                    @foreach($fabrics as $product)
                        <option
                            value="{{ $product->id }}"
                            data-base-price="{{ (float) ($product->price_500_yds ?? $product->selling_price ?? 0) }}"
                            data-unit="{{ optional($product->unit)->short_name ?? '' }}"
                            data-category="{{ optional($product->category)->name ?? '' }}"
                        >
                            {{ $product->name }}@if($product->fabric_sku ?? $product->sku) ({{ $product->fabric_sku ?? $product->sku }})@endif
                        </option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" min="{{ $projectxPositiveQuantityMin }}" step="{{ $projectxQuantityStep }}" class="form-control form-control-solid form-control-sm" data-field="qty" name="lines[__INDEX__][qty]" value="1"></td>
            <td>
                <input type="text" class="form-control form-control-solid form-control-sm" data-role="purchase-uom-display" value="" readonly>
                <input type="hidden" data-field="purchase_uom" name="lines[__INDEX__][purchase_uom]" value="">
            </td>
            <td><input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid form-control-sm" data-field="base_mill_price" name="lines[__INDEX__][base_mill_price]" value="0"></td>
            <td>
                <select class="form-select form-select-solid form-select-sm" data-field="currency" name="lines[__INDEX__][currency]">
                    @foreach($costingDropdowns['currency'] as $code => $label)
                        <option value="{{ $code }}" {{ $loop->first ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select class="form-select form-select-solid form-select-sm" data-field="incoterm" name="lines[__INDEX__][incoterm]">
                    <option value="" selected></option>
                    @foreach($costingDropdowns['incoterm'] as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </td>
            <td><span class="text-gray-900 fw-bold line-total">0.00</span></td>
            <td class="text-end">
                <button type="button" class="btn btn-icon btn-sm btn-light-danger remove-line-btn">
                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>
<style>
    .projectx-quote-header-selects select.projectx-solid-select2 + .select2-container .select2-selection--single {
        min-height: 43px;
        border: 0;
        border-radius: 0.475rem;
        background-color: var(--bs-gray-100);
        display: flex;
        align-items: center;
        padding-right: 2.25rem;
    }

    .projectx-quote-header-selects select.projectx-solid-select2 + .select2-container .select2-selection--single .select2-selection__rendered {
        padding-left: 1rem;
        padding-right: 0;
        line-height: 1.5;
        color: var(--bs-gray-700);
    }

    .projectx-quote-header-selects select.projectx-solid-select2 + .select2-container .select2-selection--single .select2-selection__placeholder {
        color: var(--bs-gray-500);
    }

    .projectx-quote-header-selects select.projectx-solid-select2 + .select2-container .select2-selection--single .select2-selection__arrow {
        right: 0.9rem;
        height: 100%;
    }
</style>
@endsection

@section('javascript')
<script>
    (function () {
        const form = document.getElementById('projectx_quote_edit_form');
        if (!form) {
            return;
        }

        const initQuoteHeaderSelects = () => {
            if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
                return;
            }

            const $form = window.jQuery(form);
            $form.find('select[data-control="select2"]').each(function () {
                const $select = window.jQuery(this);
                const allowClear = String($select.data('allow-clear')) === 'true' || $select.data('allow-clear') === true;

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    width: '100%',
                    placeholder: $select.data('placeholder') || '',
                    allowClear: allowClear
                });
            });
        };

        initQuoteHeaderSelects();

        const lineBody = document.getElementById('quote_lines_body');
        const lineTemplate = document.getElementById('quote_line_template');
        const addLineBtn = document.getElementById('add_quote_line_btn');
        const grandTotalEl = document.getElementById('quote_grand_total');
        const quoteDateInput = document.getElementById('kt_projectx_quote_edit_quote_date');
        const expiresAtInput = document.getElementById('kt_projectx_quote_edit_expires_at');

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

        initFlatpickr(quoteDateInput, {
            altInput: true,
            altFormat: 'd M, Y',
            dateFormat: 'Y-m-d',
            allowInput: false
        });
        initFlatpickr(expiresAtInput, {
            altInput: true,
            altFormat: 'd M, Y',
            dateFormat: 'Y-m-d',
            allowInput: false
        });

        const parseNum = (value) => {
            const parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : 0;
        };
        const currencyPrecision = parseInt(@json((int) $projectxCurrencyPrecision), 10) || 2;
        const formatDecimal = (value, precision) => {
            if (!Number.isFinite(value)) {
                return Number(0).toFixed(precision);
            }
            return value.toFixed(precision);
        };

        const shipmentPortInput = document.getElementById('quote_edit_shipment_port');

        const computeLine = (row) => {
            const qty = parseNum(row.querySelector('[data-field="qty"]').value);
            const base = parseNum(row.querySelector('[data-field="base_mill_price"]').value);
            return base * qty;
        };

        const applyDefaultBasePrice = (row) => {
            const baseInput = row.querySelector('[data-field="base_mill_price"]');
            const currentBase = parseNum(baseInput.value);
            const itemSelect = row.querySelector('[data-field="id"]');
            const selected = itemSelect ? itemSelect.options[itemSelect.selectedIndex] : null;

            if (!selected || !selected.dataset.basePrice || currentBase > 0) {
                return;
            }

            baseInput.value = formatDecimal(parseNum(selected.dataset.basePrice), currencyPrecision);
        };

        const syncLineMeta = (row) => {
            const itemSelect = row.querySelector('[data-field="id"]');
            const selected = itemSelect ? itemSelect.options[itemSelect.selectedIndex] : null;
            const category = (selected && selected.dataset.category) ? selected.dataset.category : '';
            const purchaseUom = (selected && selected.dataset.unit) ? selected.dataset.unit : '';

            const categoryEl = row.querySelector('.category-name');
            const purchaseUomDisplay = row.querySelector('[data-role="purchase-uom-display"]');
            const purchaseUomField = row.querySelector('[data-field="purchase_uom"]');

            if (categoryEl) {
                categoryEl.textContent = category;
            }
            if (purchaseUomDisplay) {
                purchaseUomDisplay.value = purchaseUom;
            }
            if (purchaseUomField) {
                purchaseUomField.value = purchaseUom;
            }
        };

        const syncIncotermRequirement = () => {
            const isLocalDelivery = !shipmentPortInput || shipmentPortInput.value.trim() === '';

            lineBody.querySelectorAll('.quote-line-row').forEach((row) => {
                const incotermSelect = row.querySelector('[data-field="incoterm"]');
                if (!incotermSelect) {
                    return;
                }

                incotermSelect.required = !isLocalDelivery;
                if (!isLocalDelivery && (incotermSelect.value || '') === '') {
                    const firstNonEmpty = Array.from(incotermSelect.options).find((option) => option.value !== '');
                    if (firstNonEmpty) {
                        incotermSelect.value = firstNonEmpty.value;
                    }
                }
            });
        };

        const recomputeTotals = () => {
            let grandTotal = 0;
            lineBody.querySelectorAll('.quote-line-row').forEach((row) => {
                const lineTotal = computeLine(row);
                row.querySelector('.line-total').textContent = formatDecimal(lineTotal, currencyPrecision);
                grandTotal += lineTotal;
            });

            grandTotalEl.textContent = formatDecimal(grandTotal, currencyPrecision);
        };

        const reindexRows = () => {
            lineBody.querySelectorAll('.quote-line-row').forEach((row, index) => {
                row.dataset.index = index;
                row.querySelectorAll('[data-field]').forEach((field) => {
                    field.name = `lines[${index}][${field.dataset.field}]`;
                });
            });
        };

        const attachRowEvents = (row) => {
            row.querySelectorAll('input, select').forEach((field) => {
                field.addEventListener('input', recomputeTotals);
                field.addEventListener('change', () => {
                    if (field.dataset.field === 'id') {
                        syncLineMeta(row);
                        applyDefaultBasePrice(row);
                    }
                    if (field.dataset.field === 'incoterm' || field.dataset.field === 'id') {
                        syncIncotermRequirement();
                    }
                    recomputeTotals();
                });
            });

            row.querySelector('.remove-line-btn').addEventListener('click', () => {
                if (lineBody.querySelectorAll('.quote-line-row').length === 1) {
                    return;
                }
                row.remove();
                reindexRows();
                syncIncotermRequirement();
                recomputeTotals();
            });

            syncLineMeta(row);
            applyDefaultBasePrice(row);
        };

        const addRow = () => {
            const index = lineBody.querySelectorAll('.quote-line-row').length;
            const html = lineTemplate.outerHTML.replaceAll('__INDEX__', index).replace('id="quote_line_template"', '');
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;

            lineBody.appendChild(row);
            attachRowEvents(row);
            reindexRows();
            syncIncotermRequirement();
            recomputeTotals();
        };

        addLineBtn.addEventListener('click', addRow);

        if (shipmentPortInput) {
            shipmentPortInput.addEventListener('input', syncIncotermRequirement);
            shipmentPortInput.addEventListener('change', syncIncotermRequirement);
        }

        lineBody.querySelectorAll('.quote-line-row').forEach((row) => {
            attachRowEvents(row);
        });

        syncIncotermRequirement();
        recomputeTotals();
    })();
</script>
@endsection
