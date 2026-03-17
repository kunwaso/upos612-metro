@extends('projectx::layouts.main')

@section('title', __('projectx::lang.fabric_budget'))

@section('content')
@include('projectx::fabric_manager._fabric_header')
@if($latestQuote)
    <div class="d-none" data-projectx-quote-id="{{ (int) $latestQuote->id }}"></div>
    @if(! empty($latestQuote->transaction_id))
        <div class="d-none" data-projectx-transaction-id="{{ (int) $latestQuote->transaction_id }}"></div>
    @endif
@endif

<div class="row g-5 g-xl-10">
    <div class="col-xl-8">
        <div class="card card-flush mb-5">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.single_fabric_quote') }}</h3>
            </div>
            <div class="card-body pt-5">
                <form method="POST" action="{{ route('projectx.fabric_manager.quotes.store', ['fabric_id' => $fabric->id]) }}" id="fabric_budget_quote_form">
                    @csrf
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('projectx::lang.customer') }}</label>
                            <select class="form-select form-select-solid" name="contact_id" data-control="select2" data-placeholder="{{ __('projectx::lang.customer') }}">
                                <option value=""></option>
                                @foreach($customersDropdown as $id => $label)
                                    <option value="{{ $id }}" {{ old('contact_id') == $id ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('projectx::lang.location') }}</label>
                            <select class="form-select form-select-solid" name="location_id" data-control="select2" data-placeholder="{{ __('projectx::lang.location') }}">
                                <option value=""></option>
                                @foreach($locationsDropdown as $id => $label)
                                    <option value="{{ $id }}" {{ old('location_id') == $id ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('projectx::lang.customer_email') }}</label>
                            <input type="email" class="form-control form-control-solid" name="customer_email" value="{{ old('customer_email') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('projectx::lang.customer_name') }}</label>
                            <input type="text" class="form-control form-control-solid" name="customer_name" value="{{ old('customer_name') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">{{ __('projectx::lang.quantity') }}</label>
                            <input type="number" min="{{ $projectxPositiveQuantityMin }}" step="{{ $projectxQuantityStep }}" class="form-control form-control-solid" name="qty" id="budget_qty" value="{{ old('qty', 1) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">{{ __('projectx::lang.purchase_uom') }}</label>
                            <select class="form-select form-select-solid" name="purchase_uom" id="budget_purchase_uom">
                                @foreach($costingDropdowns['purchase_uom'] as $option)
                                    <option value="{{ $option }}" {{ old('purchase_uom', $costingDropdowns['purchase_uom'][0] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('projectx::lang.base_mill_price') }}</label>
                            <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" name="base_mill_price" id="budget_base_mill_price" value="{{ old('base_mill_price', $defaultBasePriceInput ?? $defaultBasePrice) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">{{ __('projectx::lang.test_cost') }}</label>
                            <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" name="test_cost" id="budget_test_cost" value="{{ old('test_cost', 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('projectx::lang.surcharge') }}</label>
                            <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" name="surcharge" id="budget_surcharge" value="{{ old('surcharge', 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('projectx::lang.finish_uplift_pct') }}</label>
                            <input type="number" min="{{ $projectxZeroMin }}" max="1" step="{{ $projectxRateStep }}" class="form-control form-control-solid" name="finish_uplift_pct" id="budget_finish_uplift_pct" value="{{ old('finish_uplift_pct', 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('projectx::lang.waste_pct') }}</label>
                            <input type="number" min="{{ $projectxZeroMin }}" max="1" step="{{ $projectxRateStep }}" class="form-control form-control-solid" name="waste_pct" id="budget_waste_pct" value="{{ old('waste_pct', 0) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">{{ __('projectx::lang.currency_label') }}</label>
                            <select class="form-select form-select-solid" name="currency" id="budget_currency">
                                @foreach($costingDropdowns['currency'] as $code => $label)
                                    <option value="{{ $code }}" {{ old('currency', $defaultCurrencyCode ?? array_key_first($costingDropdowns['currency'] ?? [])) === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('projectx::lang.incoterm') }}</label>
                            <select class="form-select form-select-solid" name="incoterm" id="budget_incoterm">
                                @foreach($costingDropdowns['incoterm'] as $option)
                                    <option value="{{ $option }}" {{ old('incoterm', $costingDropdowns['incoterm'][0] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-7"></div>

                    <div class="row g-5 mb-7">
                        <div class="col-md-6">
                            <div class="border border-dashed border-gray-300 rounded p-5">
                                <div class="text-muted fs-7 mb-1">{{ __('projectx::lang.unit_cost') }}</div>
                                <div class="text-gray-900 fw-bolder fs-2" id="budget_unit_cost">0</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border border-dashed border-gray-300 rounded p-5">
                                <div class="text-muted fs-7 mb-1">{{ __('projectx::lang.total') }}</div>
                                <div class="text-gray-900 fw-bolder fs-2" id="budget_total_cost">0</div>
                            </div>
                        </div>
                    </div>

                    @can('projectx.quote.create')
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-duotone ki-check fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('projectx::lang.create_quote') }}
                        </button>
                    @endcan
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card card-flush">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.latest_quote_for_fabric') }}</h3>
            </div>
            <div class="card-body pt-5">
                @if($latestQuoteSummary)
                    <div class="mb-5">
                        <div class="text-gray-900 fw-bold">{{ __('projectx::lang.quote_no') }}: {{ $latestQuoteSummary['quoteNumber'] ?? '-' }}</div>
                        <div class="text-muted fs-7">{{ __('projectx::lang.created_at') }}: {{ $latestQuoteSummary['createdAtDisplay'] ?? '-' }}</div>
                        <div class="text-muted fs-7">{{ __('projectx::lang.valid_until') }}: {{ $latestQuoteSummary['validUntilDisplay'] ?? '-' }}</div>
                    </div>

                    <div class="separator separator-dashed mb-5"></div>

                    <div class="d-flex flex-stack mb-3">
                        <span class="text-gray-500 fw-semibold">{{ __('projectx::lang.quantity') }}</span>
                        <span class="text-gray-900 fw-bold">{{ $latestQuoteSummary['quantityDisplay'] ?? '0' }}</span>
                    </div>
                    <div class="d-flex flex-stack mb-3">
                        <span class="text-gray-500 fw-semibold">{{ __('projectx::lang.unit_cost') }}</span>
                        <span class="text-gray-900 fw-bold">{{ $latestQuoteSummary['unitCostDisplay'] ?? '0' }}</span>
                    </div>
                    <div class="d-flex flex-stack mb-5">
                        <span class="text-gray-500 fw-semibold">{{ __('projectx::lang.total') }}</span>
                        <span class="text-gray-900 fw-bolder">{{ $latestQuoteSummary['totalCostDisplay'] ?? '0' }}</span>
                    </div>

                    <div class="d-grid gap-3">
                        <a href="{{ route('projectx.quotes.show', ['id' => $latestQuote->id]) }}" class="btn btn-light-primary btn-sm">{{ __('projectx::lang.view_quote') }}</a>

                        @if($latestQuoteSummary['canCreateSaleFromQuote'] ?? false)
                            <a href="{{ route('sells.create', ['projectx_quote_id' => $latestQuote->id]) }}" class="btn btn-primary btn-sm">{{ __('projectx::lang.create_sale_from_quote') }}</a>
                        @endif

                        @can('projectx.quote.send')
                            <form method="POST" action="{{ route('projectx.quotes.send', ['id' => $latestQuote->id]) }}">
                                @csrf
                                <input type="hidden" name="to_email" value="{{ $latestQuoteRecipientEmail }}">
                                <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('projectx::lang.send_quote') }}</button>
                            </form>
                        @endcan
                    </div>
                @else
                    <div class="text-muted fw-semibold">{{ __('projectx::lang.no_quotes_for_fabric') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
    (function () {
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
            test: document.getElementById('budget_test_cost'),
            surcharge: document.getElementById('budget_surcharge'),
            finish: document.getElementById('budget_finish_uplift_pct'),
            waste: document.getElementById('budget_waste_pct')
        };

        const unitEl = document.getElementById('budget_unit_cost');
        const totalEl = document.getElementById('budget_total_cost');

        const compute = () => {
            const qty = parseNum(fields.qty.value);
            const base = parseNum(fields.base.value);
            const test = parseNum(fields.test.value);
            const surcharge = parseNum(fields.surcharge.value);
            const finish = parseNum(fields.finish.value);
            const waste = parseNum(fields.waste.value);

            const unit = base + test + surcharge + (base * finish) + (base * waste);
            const total = unit * qty;

            unitEl.textContent = formatDecimal(unit, currencyPrecision);
            totalEl.textContent = formatDecimal(total, currencyPrecision);
        };

        Object.values(fields).forEach((field) => {
            field.addEventListener('input', compute);
            field.addEventListener('change', compute);
        });

        compute();
    })();
</script>
@endsection
