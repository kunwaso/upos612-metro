@if($latestQuote)
    <div class="d-none" data-product-quote-id="{{ (int) $latestQuote->id }}"></div>
    @if(! empty($latestQuote->transaction_id))
        <div class="d-none" data-product-transaction-id="{{ (int) $latestQuote->transaction_id }}"></div>
    @endif
@endif

<div class="col-12">
<div class="row g-5 g-xl-10">
    <div class="col-xl-8">
        <div class="card card-flush mb-5">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-900">{{ __('product.single_product_quote') }}</h3>
            </div>
            <div class="card-body pt-5">
                <form method="POST" action="{{ route('product.quotes.store_from_product', ['product_id' => $product->id]) }}" id="product_budget_quote_form">
                    @csrf
                    <div class="row g-5 projectx-quote-header-selects">
                        <div class="col-md-6">
                            <label class="form-label required" for="budget_contact_id">{{ __('product.customer') }}</label>
                            <select class="form-select form-select-solid select2 projectx-solid-select2" name="contact_id" id="budget_contact_id" data-control="select2" data-placeholder="{{ __('product.customer') }}" data-allow-clear="true">
                                <option value="">{{ __('product.customer') }}</option>
                                @foreach($customersDropdown as $id => $label)
                                    <option value="{{ $id }}" {{ old('contact_id') == $id ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="budget_location_id">{{ __('product.location') }}</label>
                            <select class="form-select form-select-solid select2 projectx-solid-select2" name="location_id" id="budget_location_id" data-control="select2" data-placeholder="{{ __('product.location') }}" data-allow-clear="true">
                                <option value="">{{ __('product.location') }}</option>
                                @foreach($locationsDropdown as $id => $label)
                                    <option value="{{ $id }}" {{ old('location_id') == $id ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('product.customer_email') }}</label>
                            <input type="email" class="form-control form-control-solid" name="customer_email" value="{{ old('customer_email') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('product.customer_name') }}</label>
                            <input type="text" class="form-control form-control-solid" name="customer_name" value="{{ old('customer_name') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">{{ __('product.quantity') }}</label>
                            <input type="number" min="{{ $projectxPositiveQuantityMin }}" step="{{ $projectxQuantityStep }}" class="form-control form-control-solid" name="qty" id="budget_qty" value="{{ old('qty', 1) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="budget_purchase_uom_display">{{ __('product.purchase_uom') }}</label>
                            <input type="text" class="form-control form-control-solid" id="budget_purchase_uom_display" value="{{ old('purchase_uom', optional($product->unit)->short_name ?? '') }}" readonly>
                            <input type="hidden" name="purchase_uom" id="budget_purchase_uom" value="{{ old('purchase_uom', optional($product->unit)->short_name ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('product.base_mill_price') }}</label>
                            <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" name="base_mill_price" id="budget_base_mill_price" value="{{ old('base_mill_price', $defaultBasePriceInput ?? $defaultBasePrice) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required" for="budget_currency">{{ __('product.currency_label') }}</label>
                            <select class="form-select form-select-solid select2" name="currency" id="budget_currency" data-control="select2" data-hide-search="true" data-placeholder="{{ __('product.currency_label') }}">
                                @foreach($costingDropdowns['currency'] as $code => $label)
                                    <option value="{{ $code }}" {{ old('currency', $defaultCurrencyCode ?? array_key_first($costingDropdowns['currency'] ?? [])) === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="budget_incoterm">{{ __('product.incoterm') }}</label>
                            <select class="form-select form-select-solid select2" name="incoterm" id="budget_incoterm" data-control="select2" data-hide-search="true" data-placeholder="{{ __('product.incoterm') }}">
                                <option value="" {{ old('incoterm', '') === '' ? 'selected' : '' }}></option>
                                @foreach($costingDropdowns['incoterm'] as $option)
                                    <option value="{{ $option }}" {{ old('incoterm', '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="budget_shipment_port">{{ __('product.shipment_port') }}</label>
                            <input type="text" class="form-control form-control-solid" name="shipment_port" id="budget_shipment_port" list="budget_shipment_port_list" value="{{ old('shipment_port') }}" placeholder="{{ __('product.shipment_port') }}">
                            <datalist id="budget_shipment_port_list">
                                @foreach(config('product.shipment_ports', []) as $port)
                                    <option value="{{ $port }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-7"></div>

                    <div class="row g-5 mb-7">
                        <div class="col-md-6">
                            <div class="border border-dashed border-gray-300 rounded p-5">
                                <div class="text-muted fs-7 mb-1">{{ __('product.unit_cost') }}</div>
                                <div class="text-gray-900 fw-bolder fs-2" id="budget_unit_cost">0</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border border-dashed border-gray-300 rounded p-5">
                                <div class="text-muted fs-7 mb-1">{{ __('product.total') }}</div>
                                <div class="text-gray-900 fw-bolder fs-2" id="budget_total_cost">0</div>
                            </div>
                        </div>
                    </div>

                    @can('product_quote.create')
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-duotone ki-check fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('product.create_quote') }}
                        </button>
                    @endcan
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card card-flush">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-900">{{ __('product.latest_quote_for_product') }}</h3>
            </div>
            <div class="card-body pt-5">
                @if($latestQuoteSummary)
                    <div class="mb-5">
                        <div class="text-gray-900 fw-bold">{{ __('product.quote_no') }}: {{ $latestQuoteSummary['quoteNumber'] ?? '-' }}</div>
                        <div class="text-muted fs-7">{{ __('product.created_at') }}: {{ $latestQuoteSummary['createdAtDisplay'] ?? '-' }}</div>
                        <div class="text-muted fs-7">{{ __('product.valid_until') }}: {{ $latestQuoteSummary['validUntilDisplay'] ?? '-' }}</div>
                    </div>

                    <div class="separator separator-dashed mb-5"></div>

                    <div class="d-flex flex-stack mb-3">
                        <span class="text-gray-500 fw-semibold">{{ __('product.quantity') }}</span>
                        <span class="text-gray-900 fw-bold">{{ $latestQuoteSummary['quantityDisplay'] ?? '0' }}</span>
                    </div>
                    <div class="d-flex flex-stack mb-3">
                        <span class="text-gray-500 fw-semibold">{{ __('product.unit_cost') }}</span>
                        <span class="text-gray-900 fw-bold">{{ $latestQuoteSummary['unitCostDisplay'] ?? '0' }}</span>
                    </div>
                    <div class="d-flex flex-stack mb-5">
                        <span class="text-gray-500 fw-semibold">{{ __('product.total') }}</span>
                        <span class="text-gray-900 fw-bolder">{{ $latestQuoteSummary['totalCostDisplay'] ?? '0' }}</span>
                    </div>

                    <div class="d-grid gap-3">
                        <a href="{{ route('product.quotes.show', ['id' => $latestQuote->id]) }}" class="btn btn-light-primary btn-sm">{{ __('product.view_quote') }}</a>

                        @if($latestQuoteSummary['canCreateSaleFromQuote'] ?? false)
                            <a href="{{ route('sells.create', ['product_quote_id' => $latestQuote->id]) }}" class="btn btn-primary btn-sm">{{ __('product.create_sale_from_quote') }}</a>
                        @endif

                        @can('product_quote.send')
                            <form method="POST" action="{{ route('product.quotes.send', ['id' => $latestQuote->id]) }}">
                                @csrf
                                <input type="hidden" name="to_email" value="{{ $latestQuoteRecipientEmail }}">
                                <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('product.send_quote') }}</button>
                            </form>
                        @endcan
                    </div>
                @else
                    <div class="text-muted fw-semibold">{{ __('product.no_quotes_for_product') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
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
<script>
    (function () {
        const initQuoteFormSelects = () => {
            if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
                return;
            }

            const $form = window.jQuery('#product_budget_quote_form');
            if (!$form.length) {
                return;
            }

            $form.find('select[data-control="select2"]').each(function () {
                const $select = window.jQuery(this);
                const hideSearch = String($select.data('hide-search')) === 'true' || $select.data('hide-search') === true;
                const allowClear = String($select.data('allow-clear')) === 'true' || $select.data('allow-clear') === true;
                const options = {
                    width: '100%',
                    placeholder: $select.data('placeholder') || ''
                };

                if (hideSearch) {
                    options.minimumResultsForSearch = Infinity;
                }

                if (allowClear) {
                    options.allowClear = true;
                }

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.select2(options);
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initQuoteFormSelects);
        } else {
            initQuoteFormSelects();
        }

        const currencyPrecision = parseInt(@json((int) $projectxCurrencyPrecision), 10) || 2;

        const parseNum = (value) => {
            const parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : 0;
        };

        const formatDecimal = (value, precision) => {
            if (!Number.isFinite(value)) {
                return Number(0).toFixed(precision);
            }
            return value.toFixed(precision);
        };

        const fields = {
            qty: document.getElementById('budget_qty'),
            base: document.getElementById('budget_base_mill_price'),
            incoterm: document.getElementById('budget_incoterm'),
            shipmentPort: document.getElementById('budget_shipment_port')
        };

        const unitEl = document.getElementById('budget_unit_cost');
        const totalEl = document.getElementById('budget_total_cost');

        const syncIncotermRequirement = () => {
            const isLocalDelivery = !fields.shipmentPort || fields.shipmentPort.value.trim() === '';
            fields.incoterm.required = !isLocalDelivery;

            if (!isLocalDelivery && (fields.incoterm.value || '') === '') {
                const firstNonEmpty = Array.from(fields.incoterm.options).find((option) => option.value !== '');
                if (firstNonEmpty) {
                    fields.incoterm.value = firstNonEmpty.value;
                }
            }
        };

        const compute = () => {
            const qty = parseNum(fields.qty.value);
            const base = parseNum(fields.base.value);
            const unit = base;
            const total = unit * qty;

            unitEl.textContent = formatDecimal(unit, currencyPrecision);
            totalEl.textContent = formatDecimal(total, currencyPrecision);
        };

        Object.values(fields).forEach((field) => {
            field.addEventListener('input', compute);
            field.addEventListener('change', compute);
        });

        if (fields.shipmentPort) {
            fields.shipmentPort.addEventListener('input', syncIncotermRequirement);
            fields.shipmentPort.addEventListener('change', syncIncotermRequirement);
        }

        syncIncotermRequirement();
        compute();
    })();
</script>
