@extends('layouts.app')

@section('title', __('vasaccounting::lang.cutover'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.cutover'),
        'subtitle' => 'UAT sign-off, rollout controls, legacy-route retirement, and treasury parity tracking for the final VAS switchover.',
    ])

    @if (session('status.msg'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="fas fa-check-circle mt-1"></i>
            <div>{{ session('status.msg') }}</div>
        </div>
    @endif

    <div class="row g-5 g-xl-10 mb-8">
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

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Cutover blockers</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
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
                    <div class="card-title">Treasury parity snapshot</div>
                </div>
                <div class="card-body">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">UAT personas</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
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
                    <div class="card-title">Legacy route replacement map</div>
                </div>
                <div class="card-body">
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
            <div class="card-title">Rollout controls</div>
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
