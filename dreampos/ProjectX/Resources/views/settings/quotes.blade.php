@extends('projectx::layouts.main')

@section('title', __('projectx::lang.quote_settings'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('projectx::lang.quote_settings') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('projectx::lang.quote_settings_description') }}</div>
    </div>
    <a href="{{ route('projectx.sales') }}" class="btn btn-light-primary btn-sm">
        <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ __('projectx::lang.quotes') }}
    </a>
</div>

<div class="card card-flush">
    <div class="card-header pt-7">
        <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.quote_settings') }}</h3>
    </div>
    <div class="card-body pt-5">
        <form method="POST" action="{{ route('projectx.settings.quotes.update') }}">
            @csrf
            @method('PATCH')
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label required">{{ __('projectx::lang.quote_number_prefix') }}</label>
                    <input
                        type="text"
                        name="prefix"
                        class="form-control form-control-solid"
                        value="{{ old('prefix', $currentPrefix) }}"
                        maxlength="20"
                    >
                    <div class="form-text">{{ __('projectx::lang.quote_prefix_help') }}</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('projectx::lang.quote_default_currency') }}</label>
                    @php
                        $selectedDefaultCurrencyId = old('default_currency_id', $defaultCurrencyId === null ? '' : (string) $defaultCurrencyId);
                        $effectiveCurrencyLabel = ! empty($effectiveDefaultCurrencyId) ? ($currencies[$effectiveDefaultCurrencyId] ?? null) : null;
                    @endphp
                    <select
                        name="default_currency_id"
                        class="form-select form-select-solid"
                        data-control="select2"
                        data-hide-search="false"
                        data-placeholder="{{ __('projectx::lang.quote_default_currency') }}"
                    >
                        <option value="" {{ (string) $selectedDefaultCurrencyId === '' ? 'selected' : '' }}>
                            {{ __('projectx::lang.quote_default_currency_business_fallback') }}@if(! empty($effectiveCurrencyLabel)) ({{ $effectiveCurrencyLabel }})@endif
                        </option>
                        @foreach($currencies as $currencyId => $currencyLabel)
                            <option value="{{ $currencyId }}" {{ (string) $selectedDefaultCurrencyId === (string) $currencyId ? 'selected' : '' }}>
                                {{ $currencyLabel }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('projectx::lang.quote_default_currency_help') }}</div>
                </div>

                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">{{ __('projectx::lang.incoterm_options') }}</label>
                        <button type="button" class="btn btn-light-primary btn-sm" id="add_incoterm_option_btn">
                            {{ __('projectx::lang.add_option') }}
                        </button>
                    </div>

                    <div id="incoterm_options_container">
                        @php
                            $oldIncotermOptions = old('incoterm_options');
                            $incotermRows = is_array($oldIncotermOptions) ? $oldIncotermOptions : $incotermOptions;
                        @endphp
                        @foreach($incotermRows as $option)
                            <div class="input-group mb-3 quote-option-row">
                                <input type="text" name="incoterm_options[]" class="form-control form-control-solid" value="{{ $option }}" maxlength="50">
                                <button type="button" class="btn btn-light-danger remove-option-btn">{{ __('projectx::lang.remove_option') }}</button>
                            </div>
                        @endforeach
                    </div>
                    @if($isIncotermFallback)
                        <div class="form-text">{{ __('projectx::lang.quote_options_fallback_notice') }}</div>
                    @endif
                </div>

                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">{{ __('projectx::lang.purchase_uom_options') }}</label>
                        <button type="button" class="btn btn-light-primary btn-sm" id="add_purchase_uom_option_btn">
                            {{ __('projectx::lang.add_option') }}
                        </button>
                    </div>

                    <div id="purchase_uom_options_container">
                        @php
                            $oldPurchaseUomOptions = old('purchase_uom_options');
                            $purchaseUomRows = is_array($oldPurchaseUomOptions) ? $oldPurchaseUomOptions : $purchaseUomOptions;
                        @endphp
                        @foreach($purchaseUomRows as $option)
                            <div class="input-group mb-3 quote-option-row">
                                <input type="text" name="purchase_uom_options[]" class="form-control form-control-solid" value="{{ $option }}" maxlength="20">
                                <button type="button" class="btn btn-light-danger remove-option-btn">{{ __('projectx::lang.remove_option') }}</button>
                            </div>
                        @endforeach
                    </div>
                    @if($isPurchaseUomFallback)
                        <div class="form-text">{{ __('projectx::lang.quote_options_fallback_notice') }}</div>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-end mt-8">
                <button type="submit" class="btn btn-primary">
                    <i class="ki-duotone ki-check fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('projectx::lang.save_changes') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
    (function () {
        const createRow = (name, maxLength) => {
            const row = document.createElement('div');
            row.className = 'input-group mb-3 quote-option-row';
            row.innerHTML = `
                <input type="text" name="${name}" class="form-control form-control-solid" maxlength="${maxLength}">
                <button type="button" class="btn btn-light-danger remove-option-btn">{{ __('projectx::lang.remove_option') }}</button>
            `;

            return row;
        };

        const bindRemoveButtons = (container) => {
            container.querySelectorAll('.remove-option-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    button.closest('.quote-option-row')?.remove();
                });
            });
        };

        const bindAddButton = (buttonId, containerId, inputName, maxLength) => {
            const button = document.getElementById(buttonId);
            const container = document.getElementById(containerId);

            if (!button || !container) {
                return;
            }

            bindRemoveButtons(container);

            button.addEventListener('click', () => {
                const row = createRow(inputName, maxLength);
                container.appendChild(row);
                bindRemoveButtons(container);
            });
        };

        bindAddButton('add_incoterm_option_btn', 'incoterm_options_container', 'incoterm_options[]', 50);
        bindAddButton('add_purchase_uom_option_btn', 'purchase_uom_options_container', 'purchase_uom_options[]', 20);
    })();
</script>
@endsection
