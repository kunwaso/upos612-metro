@extends('layouts.app')

@section('title', __('vasaccounting::lang.cutover'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.cutover'),
        'subtitle' => 'Cutover governance board for parity checks, rollout controls, UAT completion, and provider readiness.',
    ])

    @if (session('status.msg'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-check-circle fs-2 text-success mt-1"></i>
            <div>{{ session('status.msg') }}</div>
        </div>
    @endif

    <div class="row g-5 g-xl-8 mb-8">
        @foreach ($readinessSummary as $metric)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 fw-semibold mb-2">{{ $metric['label'] }}</div>
                        <div class="text-gray-900 fw-bold fs-2">{{ $metric['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Scope</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $activeScopeLabel }}</div>
                    <div class="text-muted fs-8 mt-1">Active parity and branch view scope</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Aligned Sections</div>
                    <div class="text-success fw-bold fs-2">{{ $parityStats['aligned_sections'] }}</div>
                    <div class="text-muted fs-8 mt-1">Matched legacy and VAS totals</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Mismatches</div>
                    <div class="text-danger fw-bold fs-2">{{ $parityStats['misaligned_sections'] }}</div>
                    <div class="text-muted fs-8 mt-1">Sections still requiring reconciliation</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">Branch Rows</div>
                    <div class="text-info fw-bold fs-2">{{ $parityStats['scoped_branch_rows'] }}</div>
                    <div class="text-muted fs-8 mt-1">Rows after scope and branch filters</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Cutover Blockers</span>
                        <span class="text-muted fs-7">Critical controls that must be cleared before go-live.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Control</th>
                                    <th>Count</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($blockers as $blocker)
                                    <tr>
                                        <td>{{ $blocker['label'] }}</td>
                                        <td>{{ $blocker['count'] }}</td>
                                        <td>
                                            <span class="badge {{ $blocker['count'] > 0 ? 'badge-light-danger' : 'badge-light-success' }}">
                                                {{ $blocker['count'] > 0 ? 'Attention' : 'Ready' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Treasury Parity Snapshot</span>
                        <span class="text-muted fs-7">Legacy versus VAS balance and provider readiness checks.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Legacy accounts</span>
                            <span class="fw-bold">{{ $parity['legacy_accounts'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Legacy transactions</span>
                            <span class="fw-bold">{{ $parity['legacy_transactions'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Legacy treasury balance</span>
                            <span class="fw-bold">{{ number_format($parity['legacy_treasury_balance'], 2) }}</span>
                        </div>
                        <div class="separator"></div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">VAS posted vouchers</span>
                            <span class="fw-bold">{{ $parity['vas_posted_vouchers'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">VAS cash/bank entries</span>
                            <span class="fw-bold">{{ $parity['vas_cash_bank_entries'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">VAS treasury balance</span>
                            <span class="fw-bold">{{ number_format($parity['vas_treasury_balance'], 2) }}</span>
                        </div>
                        <div class="separator"></div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Balance delta</span>
                            <span class="fw-bold {{ abs($parity['balance_delta']) > 0.009 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($parity['balance_delta'], 2) }}
                            </span>
                        </div>
                        <div class="separator"></div>
                        <div class="text-gray-900 fw-bold">Provider Readiness</div>
                        @foreach ($providerHealth as $provider)
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-semibold text-gray-900">{{ $vasAccountingUtil->moduleAreaLabel((string) $provider['domain']) }}</div>
                                        <div class="text-muted fs-8">{{ $provider['label'] }}</div>
                                    </div>
                                    <span class="badge {{ $provider['ready'] ? 'badge-light-success' : 'badge-light-danger' }}">
                                        {{ $provider['ready'] ? 'Ready' : 'Gap' }}
                                    </span>
                                </div>
                                @if (!empty($provider['notes']))
                                    <div class="text-muted fs-8">{{ $provider['notes'] }}</div>
                                @endif
                                @if (!empty($provider['missing_config']))
                                    <div class="text-warning fs-8">Missing config: {{ implode(', ', $provider['missing_config']) }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header align-items-center">
            <div class="card-title d-flex flex-column">
                <span class="fw-bold text-gray-900">Parity Matrix</span>
                <span class="text-muted fs-7">Use period and branch controls to validate reconciliation progress.</span>
            </div>
            <div class="card-toolbar">
                <form method="GET" action="{{ route('vasaccounting.cutover.index') }}" class="d-flex align-items-end gap-4">
                    @if (!empty($selectedLocationId))
                        <input type="hidden" name="location_id" value="{{ $selectedLocationId }}">
                    @endif
                    <div>
                        <label class="form-label fs-8 text-muted mb-1">Month</label>
                        <input type="month" name="period" class="form-control form-control-solid" value="{{ $selectedPeriod }}">
                    </div>
                    <div>
                        <label class="form-label fs-8 text-muted mb-1">Branches</label>
                        <select class="form-select form-select-solid" name="branches[]" multiple data-control="select2" data-placeholder="All branches">
                            @foreach ($branchOptions as $branchId => $branchName)
                                <option value="{{ $branchId }}" {{ in_array((int) $branchId, $selectedBranches, true) ? 'selected' : '' }}>{{ $branchName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-light-primary">Refresh</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="text-muted fs-7 mb-5">
                Window: {{ $parityReport['period']['label'] }} ({{ $parityReport['period']['start_date'] }} to {{ $parityReport['period']['end_date'] }})
            </div>
            <div class="table-responsive mb-8">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Section</th>
                            <th>Legacy</th>
                            <th>VAS</th>
                            <th>Delta</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parityReport['sections'] as $section)
                            <tr>
                                <td>
                                    <div class="fw-bold text-gray-900">{{ $section['label'] }}</div>
                                    @if (!empty($section['meta']))
                                        <div class="text-muted fs-8">
                                            {{ collect($section['meta'])->map(fn ($value, $key) => ucfirst(str_replace('_', ' ', $key)) . ': ' . $value)->implode(' | ') }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ number_format($section['legacy_value'], 2) }}</td>
                                <td>{{ number_format($section['vas_value'], 2) }}</td>
                                <td class="{{ abs($section['delta']) > 0.009 ? 'text-danger fw-bold' : 'text-success fw-bold' }}">{{ number_format($section['delta'], 2) }}</td>
                                <td>
                                    <span class="badge {{ $section['status'] === 'aligned' ? 'badge-light-success' : 'badge-light-danger' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $section['status']) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="fw-bold text-gray-900 mb-4">Branch Parity</div>
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Branch</th>
                            <th>Treasury delta</th>
                            <th>AR delta</th>
                            <th>AP delta</th>
                            <th>Inventory delta</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($parityBranchRows as $branch)
                            <tr>
                                <td>{{ $branch['branch_name'] }}</td>
                                <td>{{ number_format($branch['treasury_delta'], 2) }}</td>
                                <td>{{ number_format($branch['receivables_delta'], 2) }}</td>
                                <td>{{ number_format($branch['payables_delta'], 2) }}</td>
                                <td>{{ number_format($branch['inventory_delta'], 2) }}</td>
                                <td>
                                    <span class="badge {{ $branch['overall_status'] === 'aligned' ? 'badge-light-success' : 'badge-light-danger' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $branch['overall_status']) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No branch rows are available for the selected scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">UAT Personas</span>
                        <span class="text-muted fs-7">Track readiness by persona and mark completion status.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Persona</th>
                                    <th>Focus</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($uatPersonas as $persona)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900">{{ $persona['label'] }}</div>
                                            <div class="text-muted fs-7">{{ $persona['description'] }}</div>
                                        </td>
                                        <td>{{ $persona['focus_area'] }}</td>
                                        <td>
                                            <span class="badge {{ $persona['completed'] ? 'badge-light-success' : 'badge-light-warning' }}">
                                                {{ $persona['status_label'] }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('vasaccounting.cutover.personas.update', $persona['key']) }}">
                                                @csrf
                                                <input type="hidden" name="completed" value="{{ $persona['completed'] ? 0 : 1 }}">
                                                <button type="submit" class="btn btn-sm {{ $persona['completed'] ? 'btn-light-danger' : 'btn-light-primary' }}">
                                                    {{ $persona['completed'] ? 'Reopen' : 'Mark complete' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Legacy Route Replacement</span>
                        <span class="text-muted fs-7">Legacy paths mapped to VAS cutover-safe destinations.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex flex-column gap-4">
                        @foreach ($legacyRoutes as $route)
                            <div class="border border-gray-300 rounded p-4">
                                <div class="fw-bold text-gray-900 mb-1">{{ $route['legacy_label'] }}</div>
                                <div class="text-muted fs-7 mb-2">{{ implode(', ', $route['legacy_paths']) }}</div>
                                <a href="{{ $route['route_url'] }}" class="text-primary fw-semibold">{{ $route['target_label'] }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span class="fw-bold text-gray-900">Rollout Controls</span>
                <span class="text-muted fs-7">Configure legacy redirect mode, rollout status, branch rollout set, and notes.</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('vasaccounting.cutover.settings.update') }}">
                @csrf
                <div class="row g-5">
                    <div class="col-md-4">
                        <label class="form-label">Legacy route mode</label>
                        <select class="form-select form-select-solid" name="cutover_settings[legacy_routes_mode]">
                            @foreach ($legacyModeOptions as $value => $label)
                                <option value="{{ $value }}" {{ $cutoverSettings['legacy_routes_mode'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parallel run status</label>
                        <select class="form-select form-select-solid" name="cutover_settings[parallel_run_status]">
                            @foreach ($parallelRunOptions as $value => $label)
                                <option value="{{ $value }}" {{ $cutoverSettings['parallel_run_status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rollout status</label>
                        <select class="form-select form-select-solid" name="rollout_settings[status]">
                            @foreach ($rolloutStatusOptions as $value => $label)
                                <option value="{{ $value }}" {{ $rolloutSettings['status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Target go-live date</label>
                        <input type="date" class="form-control form-control-solid" name="rollout_settings[target_go_live_date]" value="{{ $rolloutSettings['target_go_live_date'] }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Support owner</label>
                        <input type="text" class="form-control form-control-solid" name="rollout_settings[support_owner]" value="{{ $rolloutSettings['support_owner'] }}" placeholder="Finance owner">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Legacy menu</label>
                        <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                            <input class="form-check-input" type="checkbox" value="1" name="cutover_settings[hide_legacy_accounting_menu]" {{ !empty($cutoverSettings['hide_legacy_accounting_menu']) ? 'checked' : '' }}>
                            <label class="form-check-label">Hide legacy payment-account menu</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Enabled rollout branches</label>
                        <select class="form-select form-select-solid" name="rollout_settings[enabled_branch_ids][]" multiple data-control="select2" data-placeholder="Choose branches">
                            @foreach ($branchOptions as $branchId => $branchName)
                                <option value="{{ $branchId }}" {{ in_array((int) $branchId, $rolloutSettings['enabled_branch_ids'], true) ? 'selected' : '' }}>{{ $branchName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parallel run notes</label>
                        <textarea class="form-control form-control-solid" rows="4" name="cutover_settings[parallel_run_notes]" placeholder="Describe parity checks, gaps, or follow-up items">{{ $cutoverSettings['parallel_run_notes'] }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Training notes</label>
                        <textarea class="form-control form-control-solid" rows="4" name="rollout_settings[training_notes]" placeholder="Training or enablement details">{{ $rolloutSettings['training_notes'] }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rollout notes</label>
                        <textarea class="form-control form-control-solid" rows="4" name="rollout_settings[rollout_notes]" placeholder="Branch-by-branch rollout notes">{{ $rolloutSettings['rollout_notes'] }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-8">
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
