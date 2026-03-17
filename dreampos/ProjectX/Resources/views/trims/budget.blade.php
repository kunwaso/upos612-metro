@extends('projectx::layouts.main')

@section('title', __('projectx::lang.trim_budget'))

@section('content')
    @include('projectx::trims._trim_header', ['trim' => $trim ?? null, 'currency' => $currency ?? null, 'activeTab' => 'budget'])

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush mb-5">
                <div class="card-header pt-7">
                    <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.single_trim_quote') }}</h3>
                </div>
                <div class="card-body pt-5">
                    <form method="POST" action="{{ Route::has('projectx.trim_manager.quotes.store') && !empty($trim->id) ? route('projectx.trim_manager.quotes.store', ['id' => $trim->id]) : '#' }}" id="trim_budget_quote_form">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('projectx::lang.customer') }}</label>
                                <select class="form-select form-select-solid" name="contact_id" data-control="select2" data-placeholder="{{ __('projectx::lang.customer') }}">
                                    <option value=""></option>
                                    @foreach(($customersDropdown ?? []) as $id => $label)
                                        <option value="{{ $id }}" {{ old('contact_id') == $id ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">{{ __('projectx::lang.location') }}</label>
                                <select class="form-select form-select-solid" name="location_id" data-control="select2" data-placeholder="{{ __('projectx::lang.location') }}">
                                    <option value=""></option>
                                    @foreach(($locationsDropdown ?? []) as $id => $label)
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
                                <input type="number" min="{{ $projectxPositiveQuantityMin }}" step="{{ $projectxQuantityStep }}" class="form-control form-control-solid" name="qty" id="trim_budget_qty" value="{{ old('qty', 1) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">{{ __('projectx::lang.purchase_uom') }}</label>
                                <select class="form-select form-select-solid" name="purchase_uom" id="trim_budget_purchase_uom">
                                    @if(!empty(data_get($costingDropdowns ?? [], 'purchase_uom')))
                                        @foreach((data_get($costingDropdowns ?? [], 'purchase_uom', [])) as $option)
                                            <option value="{{ $option }}" {{ old('purchase_uom') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    @else
                                        <option value="pcs" {{ old('purchase_uom', $trim->unit_of_measure ?? 'pcs') === 'pcs' ? 'selected' : '' }}>{{ __('projectx::lang.uom_pcs') }}</option>
                                        <option value="cm" {{ old('purchase_uom', $trim->unit_of_measure ?? '') === 'cm' ? 'selected' : '' }}>{{ __('projectx::lang.uom_cm') }}</option>
                                        <option value="inches" {{ old('purchase_uom', $trim->unit_of_measure ?? '') === 'inches' ? 'selected' : '' }}>{{ __('projectx::lang.uom_inches') }}</option>
                                        <option value="yards" {{ old('purchase_uom', $trim->unit_of_measure ?? '') === 'yards' ? 'selected' : '' }}>{{ __('projectx::lang.uom_yards') }}</option>
                                        <option value="sets" {{ old('purchase_uom', $trim->unit_of_measure ?? '') === 'sets' ? 'selected' : '' }}>{{ __('projectx::lang.uom_sets') }}</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('projectx::lang.base_mill_price') }}</label>
                                <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" name="base_mill_price" id="trim_budget_base_price" value="{{ old('base_mill_price', $defaultBasePriceInput ?? $defaultBasePrice) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('projectx::lang.test_cost') }}</label>
                                <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" name="test_cost" id="trim_budget_test_cost" value="{{ old('test_cost', 0) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('projectx::lang.surcharge') }}</label>
                                <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" class="form-control form-control-solid" name="surcharge" id="trim_budget_surcharge" value="{{ old('surcharge', 0) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('projectx::lang.finish_uplift_pct') }}</label>
                                <input type="number" min="{{ $projectxZeroMin }}" max="1" step="{{ $projectxRateStep }}" class="form-control form-control-solid" name="finish_uplift_pct" id="trim_budget_finish_uplift_pct" value="{{ old('finish_uplift_pct', 0) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('projectx::lang.waste_pct') }}</label>
                                <input type="number" min="{{ $projectxZeroMin }}" max="1" step="{{ $projectxRateStep }}" class="form-control form-control-solid" name="waste_pct" id="trim_budget_waste_pct" value="{{ old('waste_pct', 0) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">{{ __('projectx::lang.currency_label') }}</label>
                                <select class="form-select form-select-solid" name="currency" id="trim_budget_currency">
                                    @if(!empty(data_get($costingDropdowns ?? [], 'currency')))
                                        @foreach((data_get($costingDropdowns ?? [], 'currency', [])) as $code => $label)
                                            <option value="{{ $code }}" {{ old('currency', $defaultCurrencyCode ?? '') === $code ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    @else
                                        <option value="{{ old('currency', $defaultCurrencyCode ?? 'USD') }}">{{ old('currency', $defaultCurrencyCode ?? 'USD') }}</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">{{ __('projectx::lang.incoterm') }}</label>
                                <select class="form-select form-select-solid" name="incoterm" id="trim_budget_incoterm">
                                    @if(!empty(data_get($costingDropdowns ?? [], 'incoterm')))
                                        @foreach((data_get($costingDropdowns ?? [], 'incoterm', [])) as $option)
                                            <option value="{{ $option }}" {{ old('incoterm') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    @else
                                        <option value="FOB" {{ old('incoterm', 'FOB') === 'FOB' ? 'selected' : '' }}>FOB</option>
                                        <option value="EXW" {{ old('incoterm') === 'EXW' ? 'selected' : '' }}>EXW</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-7"></div>

                        <div class="row g-5 mb-7">
                            <div class="col-md-6">
                                <div class="border border-dashed border-gray-300 rounded p-5">
                                    <div class="text-muted fs-7 mb-1">{{ __('projectx::lang.unit_cost') }}</div>
                                    <div class="text-gray-900 fw-bolder fs-2" id="trim_budget_unit_cost">0</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed border-gray-300 rounded p-5">
                                    <div class="text-muted fs-7 mb-1">{{ __('projectx::lang.total') }}</div>
                                    <div class="text-gray-900 fw-bolder fs-2" id="trim_budget_total_cost">0</div>
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
                    <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.latest_quote_for_trim') }}</h3>
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
                            <a href="{{ Route::has('projectx.quotes.show') && !empty($latestQuote->id) ? route('projectx.quotes.show', ['id' => $latestQuote->id]) : '#' }}" class="btn btn-light-primary btn-sm">
                                {{ __('projectx::lang.view_quote') }}
                            </a>

                            @can('projectx.quote.send')
                                <form method="POST" action="{{ Route::has('projectx.quotes.send') && !empty($latestQuote->id) ? route('projectx.quotes.send', ['id' => $latestQuote->id]) : '#' }}">
                                    @csrf
                                    <input type="hidden" name="to_email" value="{{ $latestQuoteRecipientEmail }}">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('projectx::lang.send_quote') }}</button>
                                </form>
                            @endcan
                        </div>
                    @else
                        <div class="text-muted fw-semibold">{{ __('projectx::lang.no_quotes_for_trim') }}</div>
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
                qty: document.getElementById('trim_budget_qty'),
                base: document.getElementById('trim_budget_base_price'),
                test: document.getElementById('trim_budget_test_cost'),
                surcharge: document.getElementById('trim_budget_surcharge'),
                finish: document.getElementById('trim_budget_finish_uplift_pct'),
                waste: document.getElementById('trim_budget_waste_pct')
            };

            const unitEl = document.getElementById('trim_budget_unit_cost');
            const totalEl = document.getElementById('trim_budget_total_cost');

            const compute = () => {
                const qty = parseNum(fields.qty ? fields.qty.value : 0);
                const base = parseNum(fields.base ? fields.base.value : 0);
                const test = parseNum(fields.test ? fields.test.value : 0);
                const surcharge = parseNum(fields.surcharge ? fields.surcharge.value : 0);
                const finish = parseNum(fields.finish ? fields.finish.value : 0);
                const waste = parseNum(fields.waste ? fields.waste.value : 0);

                const unit = base + test + surcharge + (base * finish) + (base * waste);
                const total = unit * qty;

                if (unitEl) {
                    unitEl.textContent = formatDecimal(unit, currencyPrecision);
                }
                if (totalEl) {
                    totalEl.textContent = formatDecimal(total, currencyPrecision);
                }
            };

            Object.values(fields).forEach((field) => {
                if (!field) {
                    return;
                }
                field.addEventListener('input', compute);
                field.addEventListener('change', compute);
            });

            compute();
        })();
    </script>
@endsection
