@extends('layouts.app')

@section('title', __('vasaccounting::lang.setup'))

@section('content')
    @php
        $toggleLocale = $vasAccountingLocale === 'vi' ? 'en' : 'vi';
        $toggleLocaleButtonClass = $vasAccountingLocale === 'vi' ? 'btn-light-primary' : 'btn-light-success';
        $toggleLocaleLabel = __('vasaccounting::lang.ui.' . ($vasAccountingLocale === 'vi' ? 'use_english' : 'use_vietnamese'));
        $setupActions = '<div class="d-flex flex-wrap gap-3">'
            . '<a href="' . route('vasaccounting.setup.index', ['lang' => $toggleLocale]) . '" class="btn ' . $toggleLocaleButtonClass . ' btn-sm">' . $toggleLocaleLabel . '</a>'
            . '<form method="POST" action="' . route('vasaccounting.setup.bootstrap') . '">' . csrf_field() . '<button type="submit" class="btn btn-light-primary btn-sm">' . __('vasaccounting::lang.refresh_statutory_defaults') . '</button></form>'
            . '</div>';
        $providerOptions = [
            'einvoice' => $vasAccountingUtil->providerOptions('einvoice_adapters'),
            'bank_statement_import' => $vasAccountingUtil->providerOptions('bank_statement_import_adapters'),
            'tax_export' => $vasAccountingUtil->providerOptions('tax_export_adapters'),
            'payroll_bridge' => $vasAccountingUtil->providerOptions('payroll_bridge_adapters'),
        ];
        $setupKpiCards = [
            [
                'key' => 'open_periods',
                'label' => $vasAccountingUtil->metricLabel('open_periods'),
                'value' => number_format((int) ($metrics['openPeriods'] ?? 0)),
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.open_periods'),
                'icon' => 'ki-outline ki-calendar-8',
                'badgeVariant' => 'light-primary',
            ],
            [
                'key' => 'posting_failures',
                'label' => $vasAccountingUtil->metricLabel('posting_failures'),
                'value' => number_format((int) ($metrics['postingFailures'] ?? 0)),
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.posting_failures'),
                'icon' => 'ki-outline ki-shield-cross',
                'badgeVariant' => 'light-danger',
            ],
            [
                'key' => 'compliance_completion',
                'label' => __('vasaccounting::lang.compliance_completion'),
                'value' => number_format((int) data_get($complianceCompletion ?? [], 'completion_percent', 0)) . '%',
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.compliance_completion_hint'),
                'icon' => 'ki-outline ki-shield-tick',
                'badgeVariant' => data_get($complianceCompletion ?? [], 'is_complete', false) ? 'light-success' : 'light-warning',
            ],
            [
                'key' => 'statutory_accounts',
                'label' => __('vasaccounting::lang.statutory_accounts'),
                'value' => number_format((int) data_get($bootstrapStatus, 'system_account_count', 0)),
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.views.setup.cards.manual_accounts_hint', ['count' => (int) data_get($bootstrapStatus, 'manual_account_count', 0)]),
                'icon' => 'ki-outline ki-bank',
                'badgeVariant' => 'light-success',
            ],
        ];
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

    <div class="mb-8">
        @include('vasaccounting::partials.workspace.kpi_strip', ['cards' => $setupKpiCards])
    </div>

    <div class="card card-flush mb-8">
        <div class="card-body py-6">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                <div>
                    <div class="text-gray-900 fw-bold fs-4 mb-1">{{ __('vasaccounting::lang.compliance_baseline') }}</div>
                    <div class="text-muted fs-7">
                        {{ data_get($activeComplianceProfile ?? [], 'label', 'Circular 99/2025/TT-BTC') }}
                        ({{ __('vasaccounting::lang.effective_date') }}:
                        {{ data_get($activeComplianceProfile ?? [], 'effective_date', '2026-01-01') }})
                    </div>
                </div>
                <div class="d-flex flex-column align-items-lg-end">
                    <span class="badge {{ data_get($complianceCompletion ?? [], 'is_complete', false) ? 'badge-light-success' : 'badge-light-warning' }}">
                        {{ __('vasaccounting::lang.compliance_checks') }}:
                        {{ data_get($complianceCompletion ?? [], 'completed_checks', 0) }}/{{ data_get($complianceCompletion ?? [], 'total_checks', 0) }}
                    </span>
                    @if (!empty(data_get($complianceCompletion ?? [], 'blockers')))
                        <div class="text-muted fs-8 mt-2">{{ implode(' • ', (array) data_get($complianceCompletion ?? [], 'blockers', [])) }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span>{{ $vasAccountingUtil->fieldLabel('setup_workspace') }}</span>
                        <span class="text-muted fw-semibold fs-8 mt-1">{{ __('vasaccounting::lang.setup_workspace_subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.setup.store') }}">
                        @csrf
                        <div class="mb-8">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.step') }} 1</div>
                            <div class="fw-bold fs-6 mb-4">{{ __('vasaccounting::lang.compliance_baseline') }}</div>
                            <div class="row g-5">
                                <div class="col-md-4">
                                    <label class="form-label required">{{ __('vasaccounting::lang.compliance_standard') }}</label>
                                    <select class="form-select form-select-solid" name="compliance_settings[standard]">
                                        @foreach ($complianceProfiles as $profileKey => $profileLabel)
                                            <option value="{{ $profileKey }}" @selected(old('compliance_settings.standard', data_get($activeComplianceProfile, 'key', 'tt99_2025')) === $profileKey)>{{ $profileLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">{{ __('vasaccounting::lang.effective_date') }}</label>
                                    <input
                                        type="date"
                                        class="form-control form-control-solid"
                                        name="compliance_settings[effective_date]"
                                        value="{{ old('compliance_settings.effective_date', data_get($activeComplianceProfile, 'effective_date', '2026-01-01')) }}"
                                    >
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('vasaccounting::lang.legacy_bridge_mode') }}</label>
                                    <div class="form-check form-check-custom form-check-solid mt-3">
                                        <input class="form-check-input" type="checkbox" value="1" name="compliance_settings[legacy_bridge_enabled]" {{ old('compliance_settings.legacy_bridge_enabled', data_get($activeComplianceProfile, 'legacy_bridge_enabled', false)) ? 'checked' : '' }}>
                                        <label class="form-check-label">{{ __('vasaccounting::lang.legacy_bridge_mode_help') }}</label>
                                    </div>
                                    <input type="hidden" name="compliance_settings[profile_version]" value="{{ old('compliance_settings.profile_version', data_get($activeComplianceProfile, 'profile_version', '2026.01')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-8"></div>

                        <div class="mb-8">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.step') }} 2</div>
                            <div class="fw-bold fs-6 mb-4">{{ $vasAccountingUtil->fieldLabel('core_settings') }}</div>
                            <div class="row g-5">
                                <div class="col-md-4">
                                    <label class="form-label required">{{ $vasAccountingUtil->fieldLabel('book_currency') }}</label>
                                    <input type="text" class="form-control form-control-solid" name="book_currency" value="{{ old('book_currency', $settings->book_currency) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">{{ $vasAccountingUtil->fieldLabel('inventory_method') }}</label>
                                    <select class="form-select form-select-solid" name="inventory_method">
                                        <option value="weighted_average" @selected(old('inventory_method', $settings->inventory_method) === 'weighted_average')>{{ __('vasaccounting::lang.inventory_methods.weighted_average') }}</option>
                                        <option value="fifo" @selected(old('inventory_method', $settings->inventory_method) === 'fifo')>{{ __('vasaccounting::lang.inventory_methods.fifo') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ $vasAccountingUtil->fieldLabel('module_status') }}</label>
                                    <div class="form-check form-check-custom form-check-solid mt-3">
                                        <input class="form-check-input" type="checkbox" value="1" name="is_enabled" {{ old('is_enabled', $settings->is_enabled) ? 'checked' : '' }}>
                                        <label class="form-check-label">{{ $vasAccountingUtil->fieldLabel('enabled_for_business') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">{{ $vasAccountingUtil->fieldLabel('language_preference') }}</label>
                                    <select class="form-select form-select-solid" name="ui_settings[locale]">
                                        @foreach ($localeOptions as $localeKey => $localeLabel)
                                            <option value="{{ $localeKey }}" @selected(old('ui_settings.locale', data_get($settings->ui_settings, 'locale', $vasAccountingUtil->defaultVasLocale())) === $localeKey)>{{ __('vasaccounting::lang.locales.' . $localeKey) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('vasaccounting::lang.navigation_mode') }}</label>
                                    <select class="form-select form-select-solid" name="ui_settings[navigation_mode]">
                                        <option value="advanced" @selected(old('ui_settings.navigation_mode', data_get($settings->ui_settings, 'navigation_mode', 'advanced')) === 'advanced')>{{ __('vasaccounting::lang.navigation_modes.advanced') }}</option>
                                        <option value="basic" @selected(old('ui_settings.navigation_mode', data_get($settings->ui_settings, 'navigation_mode', 'advanced')) === 'basic')>{{ __('vasaccounting::lang.navigation_modes.basic') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-8"></div>

                        <div class="mb-8">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.step') }} 3</div>
                            <div class="fw-bold fs-6 mb-4">{{ $vasAccountingUtil->fieldLabel('mandatory_posting_map') }}</div>
                            <div class="row g-5">
                                @foreach ($vasAccountingUtil->mandatoryPostingMapKeys($settings) as $postingKey)
                                    <div class="col-md-6">
                                        <label class="form-label required">{{ $vasAccountingUtil->postingMapLabel($postingKey) }}</label>
                                        <select class="form-select form-select-solid select2" data-control="select2" data-placeholder="{{ __('vasaccounting::lang.placeholders.select_account') }}" name="posting_map[{{ $postingKey }}]">
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
                        </div>

                        <div class="separator separator-dashed my-8"></div>

                        <div class="mb-8">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.step') }} 4</div>
                            <div class="fw-bold fs-6 mb-4">{{ $vasAccountingUtil->fieldLabel('workflow_and_provider_controls') }}</div>
                            <div class="row g-5">
                                <div class="col-md-4">
                                    <label class="form-label">{{ $vasAccountingUtil->fieldLabel('einvoice_provider') }}</label>
                                    <select class="form-select form-select-solid" name="einvoice_settings[provider]">
                                        @foreach ($providerOptions['einvoice'] as $providerKey => $providerLabel)
                                            <option value="{{ $providerKey }}" @selected(old('einvoice_settings.provider', data_get($settings->einvoice_settings, 'provider', 'sandbox')) === $providerKey)>{{ $providerLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ $vasAccountingUtil->fieldLabel('einvoice_mode') }}</label>
                                    <select class="form-select form-select-solid" name="einvoice_settings[mode]">
                                        <option value="sandbox" @selected(old('einvoice_settings.mode', data_get($settings->einvoice_settings, 'mode', 'sandbox')) === 'sandbox')>{{ __('vasaccounting::lang.einvoice_modes.sandbox') }}</option>
                                        <option value="production" @selected(old('einvoice_settings.mode', data_get($settings->einvoice_settings, 'mode', 'sandbox')) === 'production')>{{ __('vasaccounting::lang.einvoice_modes.production') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ $vasAccountingUtil->fieldLabel('tax_declaration_currency') }}</label>
                                    <input type="text" class="form-control form-control-solid" name="tax_settings[declaration_currency]" value="{{ old('tax_settings.declaration_currency', data_get($settings->tax_settings, 'declaration_currency', 'VND')) }}">
                                </div>
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
                                    <label class="form-label">{{ $vasAccountingUtil->fieldLabel('bank_statement_provider') }}</label>
                                    <select class="form-select form-select-solid" name="integration_settings[bank_statement_provider]">
                                        @foreach ($providerOptions['bank_statement_import'] as $providerKey => $providerLabel)
                                            <option value="{{ $providerKey }}" @selected(old('integration_settings.bank_statement_provider', data_get($settings->integration_settings, 'bank_statement_provider', 'manual')) === $providerKey)>{{ $providerLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ $vasAccountingUtil->fieldLabel('tax_export_provider') }}</label>
                                    <select class="form-select form-select-solid" name="integration_settings[tax_export_provider]">
                                        @foreach ($providerOptions['tax_export'] as $providerKey => $providerLabel)
                                            <option value="{{ $providerKey }}" @selected(old('integration_settings.tax_export_provider', data_get($settings->integration_settings, 'tax_export_provider', 'local')) === $providerKey)>{{ $providerLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ $vasAccountingUtil->fieldLabel('payroll_bridge_provider') }}</label>
                                    <select class="form-select form-select-solid" name="integration_settings[payroll_bridge_provider]">
                                        @foreach ($providerOptions['payroll_bridge'] as $providerKey => $providerLabel)
                                            <option value="{{ $providerKey }}" @selected(old('integration_settings.payroll_bridge_provider', data_get($settings->integration_settings, 'payroll_bridge_provider', 'essentials')) === $providerKey)>{{ $providerLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">VNPT API Base URL</label>
                                    <input type="text" class="form-control form-control-solid" name="integration_settings[vnpt_api_base_url]" value="{{ old('integration_settings.vnpt_api_base_url', data_get($settings->integration_settings, 'vnpt_api_base_url')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">VNPT Client ID</label>
                                    <input type="text" class="form-control form-control-solid" name="integration_settings[vnpt_client_id]" value="{{ old('integration_settings.vnpt_client_id', data_get($settings->integration_settings, 'vnpt_client_id')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">VNPT Client Secret</label>
                                    <input type="password" class="form-control form-control-solid" name="integration_settings[vnpt_client_secret]" value="{{ old('integration_settings.vnpt_client_secret') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">VNPT Tax Username</label>
                                    <input type="text" class="form-control form-control-solid" name="integration_settings[vnpt_tax_username]" value="{{ old('integration_settings.vnpt_tax_username', data_get($settings->integration_settings, 'vnpt_tax_username')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">VNPT Tax Password</label>
                                    <input type="password" class="form-control form-control-solid" name="integration_settings[vnpt_tax_password]" value="{{ old('integration_settings.vnpt_tax_password') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('vasaccounting::lang.manual_voucher_approval') }}</label>
                                    <div class="form-check form-check-custom form-check-solid mt-3">
                                        <input class="form-check-input" type="checkbox" value="1" name="approval_settings[require_manual_voucher_approval]" {{ old('approval_settings.require_manual_voucher_approval', data_get($settings->approval_settings, 'require_manual_voucher_approval', false)) ? 'checked' : '' }}>
                                        <label class="form-check-label">{{ __('vasaccounting::lang.manual_voucher_approval_help') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-8"></div>

                        <div class="mb-8">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.step') }} 5</div>
                            <div class="fw-bold fs-6 mb-4">{{ __('vasaccounting::lang.feature_flags') }}</div>
                            <div class="row g-5">
                                @foreach ($enterpriseDomains as $domainKey => $domainConfig)
                                    <div class="col-md-6">
                                        <div class="card card-bordered h-100">
                                            <div class="card-body">
                                                <div class="fw-bold text-gray-900 mb-2">{{ $domainConfig['title'] }}</div>
                                                <div class="text-muted fs-8 mb-4">{{ $domainConfig['subtitle'] }}</div>
                                                <div class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" value="1" name="feature_flags[{{ $domainKey }}]" {{ old('feature_flags.' . $domainKey, data_get($settings->feature_flags, $domainKey, true)) ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ $vasAccountingUtil->actionLabel('enabled') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">{{ $vasAccountingUtil->actionLabel('save_setup') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">{{ $vasAccountingUtil->fieldLabel('bootstrap_readiness') }}</div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted fs-7">{{ $vasAccountingUtil->fieldLabel('accounts') }}</span>
                            <span class="fw-bold text-gray-900">{{ data_get($bootstrapStatus, 'account_count', 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted fs-7">{{ $vasAccountingUtil->fieldLabel('tax_codes') }}</span>
                            <span class="fw-bold text-gray-900">{{ data_get($bootstrapStatus, 'tax_code_count', 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted fs-7">{{ $vasAccountingUtil->fieldLabel('sequences') }}</span>
                            <span class="fw-bold text-gray-900">{{ data_get($bootstrapStatus, 'sequence_count', 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted fs-7">{{ $vasAccountingUtil->fieldLabel('periods') }}</span>
                            <span class="fw-bold text-gray-900">{{ data_get($bootstrapStatus, 'period_count', 0) }}</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted fs-7">{{ $vasAccountingUtil->fieldLabel('status') }}</span>
                            <span class="badge {{ data_get($bootstrapStatus, 'needs_bootstrap') ? 'badge-light-warning' : 'badge-light-success' }}">
                                {{ data_get($bootstrapStatus, 'needs_bootstrap') ? __('vasaccounting::lang.bootstrap_needed') : __('vasaccounting::lang.bootstrap_ready') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">{{ $vasAccountingUtil->fieldLabel('quick_actions') }}</div>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    <a href="{{ route('vasaccounting.dashboard.index') }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open_dashboard') }}</a>
                    <a href="{{ route('vasaccounting.chart.index') }}" class="btn btn-light btn-sm">{{ $vasAccountingUtil->actionLabel('chart_of_accounts') }}</a>
                    <a href="{{ route('vasaccounting.reports.index') }}" class="btn btn-light btn-sm">{{ $vasAccountingUtil->actionLabel('reports_hub') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
