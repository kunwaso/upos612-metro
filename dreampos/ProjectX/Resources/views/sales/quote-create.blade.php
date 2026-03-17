@extends('projectx::layouts.main')

@section('title', __('projectx::lang.create_quote'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('projectx::lang.create_quote') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('projectx::lang.create_multi_quote_help') }}</div>
    </div>
    <a href="{{ route('projectx.sales') }}" class="btn btn-light-primary btn-sm">
        <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ __('projectx::lang.back_to_quotes') }}
    </a>
</div>

<form method="POST" action="{{ route('projectx.quotes.store') }}" id="projectx_quote_create_form">
    @csrf

    <div class="card card-flush mb-6">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.quote_header') }}</h3>
        </div>
        <div class="card-body pt-5">
            <div class="row g-5">
                <div class="col-md-4">
                    <label class="form-label required">{{ __('projectx::lang.customer') }}</label>
                    <select class="form-select form-select-solid" name="contact_id" data-control="select2" data-placeholder="{{ __('projectx::lang.customer') }}">
                        <option value=""></option>
                        @foreach($customersDropdown as $id => $label)
                            <option value="{{ $id }}" {{ old('contact_id') == $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label required">{{ __('projectx::lang.location') }}</label>
                    <select class="form-select form-select-solid" name="location_id" data-control="select2" data-placeholder="{{ __('projectx::lang.location') }}">
                        <option value=""></option>
                        @foreach($locationsDropdown as $id => $label)
                            <option value="{{ $id }}" {{ old('location_id') == $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('projectx::lang.customer_email') }}</label>
                    <input type="email" class="form-control form-control-solid" name="customer_email" value="{{ old('customer_email') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('projectx::lang.customer_name') }}</label>
                    <input type="text" class="form-control form-control-solid" name="customer_name" value="{{ old('customer_name') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('projectx::lang.quote_date') }}</label>
                    <input type="text" class="form-control form-control-solid" id="kt_projectx_quote_create_quote_date" name="quote_date" value="{{ old('quote_date', now()->format('Y-m-d')) }}" placeholder="{{ __('projectx::lang.quote_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label required">{{ __('projectx::lang.valid_until') }}</label>
                    <input type="text" class="form-control form-control-solid" id="kt_projectx_quote_create_expires_at" name="expires_at" value="{{ old('expires_at', now()->addDays(14)->format('Y-m-d')) }}" placeholder="{{ __('projectx::lang.valid_until') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('projectx::lang.shipment_port') }}</label>
                    <input type="text" class="form-control form-control-solid" name="shipment_port" list="shipment_port_list" value="{{ old('shipment_port') }}" placeholder="{{ __('projectx::lang.shipment_port') }}">
                    <datalist id="shipment_port_list">
                        @foreach(config('projectx.shipment_ports', []) as $port)
                            <option value="{{ $port }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('projectx::lang.remark') }}</label>
                    <textarea class="form-control form-control-solid" name="remark" rows="6" placeholder="{{ __('projectx::lang.remark') }}">{{ old('remark', config('projectx.quote_defaults.remark', '')) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-6">
        <div class="card-header pt-7 d-flex justify-content-between">
            <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.quote_lines') }}</h3>
            <button type="button" class="btn btn-light-primary btn-sm" id="add_quote_line_btn">
                <i class="ki-duotone ki-plus fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('projectx::lang.add_line') }}
            </button>
        </div>
        <div class="card-body pt-5">
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4" id="quote_lines_table">
                    <thead>
                        <tr class="fw-bold text-muted fs-7 text-uppercase">
                            <th>{{ __('projectx::lang.line_type') }}</th>
                            <th>{{ __('projectx::lang.quote_line_item') }}</th>
                            <th>{{ __('projectx::lang.quantity') }}</th>
                            <th>{{ __('projectx::lang.purchase_uom') }}</th>
                            <th>{{ __('projectx::lang.base_mill_price') }}</th>
                            <th>{{ __('projectx::lang.test_cost') }}</th>
                            <th>{{ __('projectx::lang.surcharge') }}</th>
                            <th>{{ __('projectx::lang.finish_uplift_pct') }}</th>
                            <th>{{ __('projectx::lang.waste_pct') }}</th>
                            <th>{{ __('projectx::lang.currency_label') }}</th>
                            <th>{{ __('projectx::lang.incoterm') }}</th>
                            <th>{{ __('projectx::lang.total') }}</th>
                            <th class="text-end">{{ __('projectx::lang.action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="quote_lines_body">
                        @foreach($quoteLines as $index => $line)
                            <tr class="quote-line-row" data-index="{{ $index }}">
                                <td>
                                    <select class="form-select form-select-solid form-select-sm" data-field="line_type" name="lines[{{ $index }}][line_type]">
                                        <option value="fabric" {{ ($line['line_type'] ?? 'fabric') === 'fabric' ? 'selected' : '' }}>{{ __('projectx::lang.line_type_fabric') }}</option>
                                        <option value="trim" {{ ($line['line_type'] ?? 'fabric') === 'trim' ? 'selected' : '' }}>{{ __('projectx::lang.line_type_trim') }}</option>
                                    </select>
                                </td>
                                <td data-role="fabric-cell">
                                    <select class="form-select form-select-solid form-select-sm" data-field="fabric_id" name="lines[{{ $index }}][fabric_id]">
                                        <option value="">{{ __('projectx::lang.select_fabric') }}</option>
                                        @foreach($fabrics as $fabric)
                                            <option value="{{ $fabric->id }}" data-base-price="{{ (float) ($fabric->price_500_yds ?? 0) }}" {{ (string) ($line['fabric_id'] ?? '') === (string) $fabric->id ? 'selected' : '' }}>
                                                {{ $fabric->name }}@if($fabric->fabric_sku) ({{ $fabric->fabric_sku }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td data-role="trim-cell">
                                    <select class="form-select form-select-solid form-select-sm" data-field="trim_id" name="lines[{{ $index }}][trim_id]">
                                        <option value="">{{ __('projectx::lang.select_trim') }}</option>
                                        @foreach($trims as $trim)
                                            <option value="{{ $trim->id }}" data-base-price="{{ (float) ($trim->unit_cost ?? 0) }}" {{ (string) ($line['trim_id'] ?? '') === (string) $trim->id ? 'selected' : '' }}>
                                                {{ $trim->name }}@if($trim->part_number) ({{ $trim->part_number }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" min="{{ $projectxPositiveQuantityMin }}" step="{{ $projectxQuantityStep }}" class="form-control form-control-solid form-control-sm" data-field="qty" name="lines[{{ $index }}][qty]" value="{{ $line['qty'] ?? 1 }}"></td>
                                <td>
                                    <select class="form-select form-select-solid form-select-sm" data-field="purchase_uom" name="lines[{{ $index }}][purchase_uom]">
                                        @foreach($costingDropdowns['purchase_uom'] as $option)
                                            <option value="{{ $option }}" {{ (string) ($line['purchase_uom'] ?? '') === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid form-control-sm" data-field="base_mill_price" name="lines[{{ $index }}][base_mill_price]" value="{{ $line['base_mill_price'] ?? 0 }}"></td>
                                <td><input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid form-control-sm" data-field="test_cost" name="lines[{{ $index }}][test_cost]" value="{{ $line['test_cost'] ?? 0 }}"></td>
                                <td><input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid form-control-sm" data-field="surcharge" name="lines[{{ $index }}][surcharge]" value="{{ $line['surcharge'] ?? 0 }}"></td>
                                <td><input type="number" min="{{ $projectxZeroMin }}" max="1" step="{{ $projectxRateStep }}" class="form-control form-control-solid form-control-sm" data-field="finish_uplift_pct" name="lines[{{ $index }}][finish_uplift_pct]" value="{{ $line['finish_uplift_pct'] ?? 0 }}"></td>
                                <td><input type="number" min="{{ $projectxZeroMin }}" max="1" step="{{ $projectxRateStep }}" class="form-control form-control-solid form-control-sm" data-field="waste_pct" name="lines[{{ $index }}][waste_pct]" value="{{ $line['waste_pct'] ?? 0 }}"></td>
                                <td>
                                    <select class="form-select form-select-solid form-select-sm" data-field="currency" name="lines[{{ $index }}][currency]">
                                        @foreach($costingDropdowns['currency'] as $code => $label)
                                            <option value="{{ $code }}" {{ (string) ($line['currency'] ?? '') === (string) $code ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-solid form-select-sm" data-field="incoterm" name="lines[{{ $index }}][incoterm]">
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
                    <div class="text-muted fs-7">{{ __('projectx::lang.grand_total') }}</div>
                    <div class="fw-bolder fs-2 text-gray-900" id="quote_grand_total">0.00</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i class="ki-duotone ki-check fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('projectx::lang.create_quote') }}
        </button>
    </div>
</form>

<table class="d-none">
    <tbody>
        <tr id="quote_line_template" class="quote-line-row" data-index="__INDEX__">
            <td>
                <select class="form-select form-select-solid form-select-sm" data-field="line_type" name="lines[__INDEX__][line_type]">
                    <option value="fabric">{{ __('projectx::lang.line_type_fabric') }}</option>
                    <option value="trim">{{ __('projectx::lang.line_type_trim') }}</option>
                </select>
            </td>
            <td data-role="fabric-cell">
                <select class="form-select form-select-solid form-select-sm" data-field="fabric_id" name="lines[__INDEX__][fabric_id]">
                    <option value="">{{ __('projectx::lang.select_fabric') }}</option>
                    @foreach($fabrics as $fabric)
                        <option value="{{ $fabric->id }}" data-base-price="{{ (float) ($fabric->price_500_yds ?? 0) }}">
                            {{ $fabric->name }}@if($fabric->fabric_sku) ({{ $fabric->fabric_sku }})@endif
                        </option>
                    @endforeach
                </select>
            </td>
            <td data-role="trim-cell">
                <select class="form-select form-select-solid form-select-sm" data-field="trim_id" name="lines[__INDEX__][trim_id]">
                    <option value="">{{ __('projectx::lang.select_trim') }}</option>
                    @foreach($trims as $trim)
                        <option value="{{ $trim->id }}" data-base-price="{{ (float) ($trim->unit_cost ?? 0) }}">
                            {{ $trim->name }}@if($trim->part_number) ({{ $trim->part_number }})@endif
                        </option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" min="{{ $projectxPositiveQuantityMin }}" step="{{ $projectxQuantityStep }}" class="form-control form-control-solid form-control-sm" data-field="qty" name="lines[__INDEX__][qty]" value="1"></td>
            <td>
                <select class="form-select form-select-solid form-select-sm" data-field="purchase_uom" name="lines[__INDEX__][purchase_uom]">
                    @foreach($costingDropdowns['purchase_uom'] as $option)
                        <option value="{{ $option }}" {{ $loop->first ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid form-control-sm" data-field="base_mill_price" name="lines[__INDEX__][base_mill_price]" value="0"></td>
            <td><input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid form-control-sm" data-field="test_cost" name="lines[__INDEX__][test_cost]" value="0"></td>
            <td><input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid form-control-sm" data-field="surcharge" name="lines[__INDEX__][surcharge]" value="0"></td>
            <td><input type="number" min="{{ $projectxZeroMin }}" max="1" step="{{ $projectxRateStep }}" class="form-control form-control-solid form-control-sm" data-field="finish_uplift_pct" name="lines[__INDEX__][finish_uplift_pct]" value="0"></td>
            <td><input type="number" min="{{ $projectxZeroMin }}" max="1" step="{{ $projectxRateStep }}" class="form-control form-control-solid form-control-sm" data-field="waste_pct" name="lines[__INDEX__][waste_pct]" value="0"></td>
            <td>
                <select class="form-select form-select-solid form-select-sm" data-field="currency" name="lines[__INDEX__][currency]">
                    @foreach($costingDropdowns['currency'] as $code => $label)
                        <option value="{{ $code }}" {{ $loop->first ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select class="form-select form-select-solid form-select-sm" data-field="incoterm" name="lines[__INDEX__][incoterm]">
                    @foreach($costingDropdowns['incoterm'] as $option)
                        <option value="{{ $option }}" {{ $loop->first ? 'selected' : '' }}>{{ $option }}</option>
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
@endsection

@section('page_javascript')
<script>
    (function () {
        const form = document.getElementById('projectx_quote_create_form');
        if (!form) {
            return;
        }

        const lineBody = document.getElementById('quote_lines_body');
        const lineTemplate = document.getElementById('quote_line_template');
        const addLineBtn = document.getElementById('add_quote_line_btn');
        const grandTotalEl = document.getElementById('quote_grand_total');
        const quoteDateInput = document.getElementById('kt_projectx_quote_create_quote_date');
        const expiresAtInput = document.getElementById('kt_projectx_quote_create_expires_at');

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

        const computeLine = (row) => {
            const qty = parseNum(row.querySelector('[data-field="qty"]').value);
            const base = parseNum(row.querySelector('[data-field="base_mill_price"]').value);
            const testCost = parseNum(row.querySelector('[data-field="test_cost"]').value);
            const surcharge = parseNum(row.querySelector('[data-field="surcharge"]').value);
            const finish = parseNum(row.querySelector('[data-field="finish_uplift_pct"]').value);
            const waste = parseNum(row.querySelector('[data-field="waste_pct"]').value);

            const unit = base + testCost + surcharge + (base * finish) + (base * waste);
            return unit * qty;
        };

        const getLineType = (row) => {
            const typeValue = (row.querySelector('[data-field="line_type"]').value || '').toLowerCase();
            return typeValue === 'trim' ? 'trim' : 'fabric';
        };

        const applyDefaultBasePrice = (row) => {
            const lineType = getLineType(row);
            const baseInput = row.querySelector('[data-field="base_mill_price"]');
            const currentBase = parseNum(baseInput.value);
            const selector = lineType === 'trim' ? '[data-field="trim_id"]' : '[data-field="fabric_id"]';
            const itemSelect = row.querySelector(selector);
            const selected = itemSelect ? itemSelect.options[itemSelect.selectedIndex] : null;

            if (!selected || !selected.dataset.basePrice || currentBase > 0) {
                return;
            }

            baseInput.value = formatDecimal(parseNum(selected.dataset.basePrice), currencyPrecision);
        };

        const updateLineTypeState = (row) => {
            const lineType = getLineType(row);
            const fabricCell = row.querySelector('[data-role="fabric-cell"]');
            const trimCell = row.querySelector('[data-role="trim-cell"]');
            const fabricSelect = row.querySelector('[data-field="fabric_id"]');
            const trimSelect = row.querySelector('[data-field="trim_id"]');

            const isTrim = lineType === 'trim';

            fabricCell.classList.toggle('d-none', isTrim);
            trimCell.classList.toggle('d-none', !isTrim);

            fabricSelect.disabled = isTrim;
            trimSelect.disabled = !isTrim;

            if (isTrim) {
                fabricSelect.value = '';
            } else {
                trimSelect.value = '';
            }

            applyDefaultBasePrice(row);
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
                    if (field.dataset.field === 'line_type') {
                        updateLineTypeState(row);
                    }
                    if (field.dataset.field === 'fabric_id' || field.dataset.field === 'trim_id') {
                        applyDefaultBasePrice(row);
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
                recomputeTotals();
            });

            updateLineTypeState(row);
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
            recomputeTotals();
        };

        addLineBtn.addEventListener('click', addRow);

        lineBody.querySelectorAll('.quote-line-row').forEach((row) => {
            attachRowEvents(row);
        });

        recomputeTotals();
    })();
</script>
@endsection
