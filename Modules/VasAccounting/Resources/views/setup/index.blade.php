@extends('layouts.app')

@section('title', __('vasaccounting::lang.setup'))

@section('content')
    @php
        $setupActions = '<form method="POST" action="' . route('vasaccounting.setup.bootstrap') . '">' . csrf_field() . '<button type="submit" class="btn btn-light-primary btn-sm">' . __('vasaccounting::lang.refresh_statutory_defaults') . '</button></form>';
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.module_name'),
        'subtitle' => __('vasaccounting::lang.setup_subtitle'),
        'actions' => $setupActions,
    ])

    @if (!empty($autoBootstrapped))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="fas fa-check-circle mt-1"></i>
            <div>
                <div class="fw-bold">{{ __('vasaccounting::lang.auto_bootstrap_title') }}</div>
                <div class="text-muted">{{ __('vasaccounting::lang.auto_bootstrap_body') }}</div>
            </div>
        </div>
    @endif

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Open periods</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['openPeriods'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Posting failures</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['postingFailures'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Draft vouchers</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $metrics['draftVouchers'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.statutory_accounts') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ data_get($bootstrapStatus, 'system_account_count', 0) }}</div>
                    <div class="text-muted fs-8 mt-2">{{ __('vasaccounting::lang.manual_accounts') }}: {{ data_get($bootstrapStatus, 'manual_account_count', 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title">Setup Wizard</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('vasaccounting.setup.store') }}">
                @csrf
                <div class="row g-5 mb-8">
                    <div class="col-md-4">
                        <label class="form-label required">Book currency</label>
                        <input type="text" class="form-control form-control-solid" name="book_currency" value="{{ old('book_currency', $settings->book_currency) }}">
                        <div class="text-muted fs-8 mt-2">{{ __('vasaccounting::lang.bootstrap_helper_text') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Inventory method</label>
                        <input type="text" class="form-control form-control-solid" name="inventory_method" value="{{ old('inventory_method', $settings->inventory_method) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Module status</label>
                        <div class="form-check form-check-custom form-check-solid mt-3">
                            <input class="form-check-input" type="checkbox" value="1" name="is_enabled" {{ old('is_enabled', $settings->is_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label">Enabled for this business</label>
                        </div>
                    </div>
                </div>

                <div class="row g-5 mb-8">
                    @foreach (config('vasaccounting.mandatory_posting_map_keys', []) as $postingKey)
                        <div class="col-md-4">
                            <label class="form-label required text-capitalize">{{ str_replace('_', ' ', $postingKey) }}</label>
                            <select class="form-select form-select-solid select2" data-control="select2" data-placeholder="Select account" name="posting_map[{{ $postingKey }}]">
                                <option value=""></option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" {{ (string) data_get($selectedPostingMap ?? [], $postingKey) === (string) $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="row g-5 mb-8">
                    <div class="col-md-4">
                        <label class="form-label">E-invoice provider</label>
                        <input type="text" class="form-control form-control-solid" name="einvoice_settings[provider]" value="{{ old('einvoice_settings.provider', data_get($settings->einvoice_settings, 'provider', 'sandbox')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">E-invoice mode</label>
                        <input type="text" class="form-control form-control-solid" name="einvoice_settings[mode]" value="{{ old('einvoice_settings.mode', data_get($settings->einvoice_settings, 'mode', 'sandbox')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tax declaration currency</label>
                        <input type="text" class="form-control form-control-solid" name="tax_settings[declaration_currency]" value="{{ old('tax_settings.declaration_currency', data_get($settings->tax_settings, 'declaration_currency', 'VND')) }}">
                    </div>
                </div>

                <div class="separator separator-dashed my-8"></div>

                <div class="row g-5 mb-8">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('vasaccounting::lang.approval_default_status') }}</label>
                        <select class="form-select form-select-solid" name="approval_settings[default_manual_voucher_status]">
                            @foreach ($documentStatuses as $statusKey => $statusLabel)
                                @if (in_array($statusKey, ['draft', 'pending_approval', 'approved'], true))
                                    <option value="{{ $statusKey }}" {{ old('approval_settings.default_manual_voucher_status', data_get($settings->approval_settings, 'default_manual_voucher_status', 'draft')) === $statusKey ? 'selected' : '' }}>
                                        {{ $statusLabel }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('vasaccounting::lang.api_guard') }}</label>
                        <input type="text" class="form-control form-control-solid" name="integration_settings[api_guard]" value="{{ old('integration_settings.api_guard', data_get($settings->integration_settings, 'api_guard', 'auth:api')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('vasaccounting::lang.integration_provider') }}</label>
                        <input type="text" class="form-control form-control-solid" name="integration_settings[bank_statement_provider]" value="{{ old('integration_settings.bank_statement_provider', data_get($settings->integration_settings, 'bank_statement_provider', 'manual')) }}">
                        <div class="text-muted fs-8 mt-2">Bank statement import adapter key</div>
                    </div>
                </div>

                <div class="row g-5 mb-8">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('vasaccounting::lang.integration_provider') }}</label>
                        <input type="text" class="form-control form-control-solid" name="integration_settings[tax_export_provider]" value="{{ old('integration_settings.tax_export_provider', data_get($settings->integration_settings, 'tax_export_provider', 'local')) }}">
                        <div class="text-muted fs-8 mt-2">Tax export adapter key</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('vasaccounting::lang.integration_provider') }}</label>
                        <input type="text" class="form-control form-control-solid" name="integration_settings[payroll_bridge_provider]" value="{{ old('integration_settings.payroll_bridge_provider', data_get($settings->integration_settings, 'payroll_bridge_provider', 'essentials')) }}">
                        <div class="text-muted fs-8 mt-2">Payroll bridge adapter key</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('vasaccounting::lang.manual_voucher_approval') }}</label>
                        <div class="form-check form-check-custom form-check-solid mt-3">
                            <input class="form-check-input" type="checkbox" value="1" name="approval_settings[require_manual_voucher_approval]" {{ old('approval_settings.require_manual_voucher_approval', data_get($settings->approval_settings, 'require_manual_voucher_approval', false)) ? 'checked' : '' }}>
                            <label class="form-check-label">Enable approval queue before posting</label>
                        </div>
                    </div>
                </div>

                <div class="separator separator-dashed my-8"></div>

                <div class="mb-8">
                    <div class="fw-bold fs-5 mb-4">{{ __('vasaccounting::lang.feature_flags') }}</div>
                    <div class="row g-5">
                        @foreach ($enterpriseDomains as $domainKey => $domainConfig)
                            <div class="col-md-4">
                                <div class="card card-bordered h-100">
                                    <div class="card-body">
                                        <div class="fw-bold text-gray-900 mb-2">{{ $domainConfig['title'] }}</div>
                                        <div class="text-muted fs-8 mb-4">{{ $domainConfig['subtitle'] }}</div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" name="feature_flags[{{ $domainKey }}]" {{ old('feature_flags.' . $domainKey, data_get($settings->feature_flags, $domainKey, true)) ? 'checked' : '' }}>
                                            <label class="form-check-label">Enabled</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Save VAS Setup</button>
                </div>
            </form>
        </div>
    </div>
@endsection
