@extends('layouts.app')

@section('title', __('lang_v1.control_tower'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">@lang('lang_v1.control_tower')</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.control_tower')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.settings.index') }}" class="btn btn-sm btn-light">@lang('lang_v1.warehouse_settings')</a>
                <a href="{{ route('storage-manager.areas.index') }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.warehouse_areas')</a>
                <a href="{{ route('storage-manager.planning.index', ['location_id' => $locationId]) }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.purchasing_advisories')</a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @if(session('status'))
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-6">
                    {{ session('status.msg') }}
                </div>
            @endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.open_documents')</div><div class="fs-2hx fw-bold">{{ $metrics['open_documents'] }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.open_tasks')</div><div class="fs-2hx fw-bold">{{ $metrics['open_tasks'] }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.sync_errors')</div><div class="fs-2hx fw-bold">{{ $metrics['sync_errors'] }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.bypass_alerts')</div><div class="fs-2hx fw-bold">{{ $metrics['bypass_alerts'] }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.available_bins')</div><div class="fs-2hx fw-bold">{{ $metrics['available_bins'] }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.quarantine_bins')</div><div class="fs-2hx fw-bold">{{ $metrics['quarantine_bins'] }}</div></div></div></div>
            </div>

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.strict_ready_locations')</div><div class="fs-2hx fw-bold">{{ $dashboard['headlineMetrics']['strict_ready_locations'] ?? 0 }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.mismatch_locations')</div><div class="fs-2hx fw-bold">{{ $dashboard['headlineMetrics']['mismatch_locations'] ?? 0 }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.bypass_events')</div><div class="fs-2hx fw-bold">{{ $dashboard['headlineMetrics']['bypass_events'] ?? 0 }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.bin_occupancy')</div><div class="fs-2hx fw-bold">{{ data_get($dashboard, 'kpis.occupancy_rate.label', '—') }}</div><div class="text-muted fs-8">{{ data_get($dashboard, 'kpis.occupancy_rate.detail', '—') }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.count_accuracy')</div><div class="fs-2hx fw-bold">{{ data_get($dashboard, 'kpis.count_accuracy_rate.label', '—') }}</div><div class="text-muted fs-8">{{ data_get($dashboard, 'kpis.count_accuracy_rate.detail', '—') }}</div></div></div></div>
                <div class="col-md-2"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.damage_rate')</div><div class="fs-2hx fw-bold">{{ data_get($dashboard, 'kpis.damage_rate.label', '—') }}</div><div class="text-muted fs-8">{{ data_get($dashboard, 'kpis.damage_rate.detail', '—') }}</div></div></div></div>
            </div>

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.purchasing_review_rows')</div><div class="fs-2hx fw-bold">{{ (int) data_get($purchasingSummary, 'summary.shortage_count', 0) }}</div></div></div></div>
                <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.external_shortage_qty')</div><div class="fs-2hx fw-bold">{{ format_quantity_value(data_get($purchasingSummary, 'summary.total_external_shortage_qty', 0)) }}</div></div></div></div>
                <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.open_purchase_requisitions')</div><div class="fs-2hx fw-bold">{{ (int) data_get($purchasingSummary, 'summary.open_requisitions', 0) }}</div></div></div></div>
            </div>

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-6">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.transfer_cycle_time')</div>
                            <div class="fs-2hx fw-bold">{{ data_get($dashboard, 'kpis.transfer_cycle_hours.label', '—') }}</div>
                            <div class="text-muted fs-8">{{ data_get($dashboard, 'kpis.transfer_cycle_hours.detail', '—') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.dock_to_stock_time')</div>
                            <div class="fs-2hx fw-bold">{{ data_get($dashboard, 'kpis.dock_to_stock_hours.label', '—') }}</div>
                            <div class="text-muted fs-8">{{ data_get($dashboard, 'kpis.dock_to_stock_hours.detail', '—') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.control-tower.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-sm form-select-solid w-250px" onchange="this.form.submit()">
                            <option value="0">@lang('messages.all')</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected($locationId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.location_reconciliation_status')</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="fw-bold text-gray-800">
                                    <th>@lang('business.location')</th>
                                    <th>@lang('lang_v1.execution_mode')</th>
                                    <th>@lang('lang_v1.slot_stock_total')</th>
                                    <th>@lang('lang_v1.source_stock_total')</th>
                                    <th>@lang('lang_v1.mismatch_count')</th>
                                    <th>@lang('lang_v1.vas_sync')</th>
                                    <th>@lang('lang_v1.lot_expiry_ready')</th>
                                    <th>@lang('lang_v1.status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($locationRows as $row)
                                    @php $readiness = $readinessRows[(int) $row['location_id']] ?? ['ready' => true, 'lot_missing_count' => 0, 'expiry_missing_count' => 0]; @endphp
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row['location_name'] ?: ('#' . $row['location_id']) }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $row['execution_mode'])) }}</td>
                                        <td>{{ @format_quantity($row['slot_total']) }}</td>
                                        <td>{{ @format_quantity($row['source_total']) }}</td>
                                        <td>
                                            <span class="badge {{ $row['mismatch_count'] > 0 ? 'badge-light-danger' : 'badge-light-success' }}">
                                                {{ $row['mismatch_count'] }}
                                            </span>
                                        </td>
                                        <td>
                                            @php $syncSummary = $row['sync_summary']; @endphp
                                            <div class="text-gray-700 fs-7">
                                                Pending: {{ $syncSummary['pending_sync'] }}<br>
                                                Errors: {{ $syncSummary['sync_errors'] + $syncSummary['reconcile_errors'] }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ ($readiness['ready'] ?? false) ? 'badge-light-success' : 'badge-light-warning' }}">
                                                {{ ($readiness['ready'] ?? false) ? __('lang_v1.ready') : __('lang_v1.attention_required') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $row['has_blockers'] ? 'badge-light-danger' : 'badge-light-success' }}">
                                                {{ $row['has_blockers'] ? __('lang_v1.blocked') : __('lang_v1.aligned') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-8">@lang('lang_v1.no_reconciliation_data')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.rollout_readiness')</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="fw-bold text-gray-800">
                                    <th>@lang('business.location')</th>
                                    <th>@lang('lang_v1.execution_mode')</th>
                                    <th>@lang('lang_v1.bypass_policy')</th>
                                    <th>@lang('lang_v1.closed_count_sessions')</th>
                                    <th>@lang('lang_v1.lot_expiry_ready')</th>
                                    <th>@lang('lang_v1.strict_mode_ready')</th>
                                    <th>@lang('lang_v1.reason')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($dashboard['rolloutRows'] ?? collect()) as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row['location_name'] ?? '-' }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', (string) ($row['execution_mode'] ?? 'off'))) }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', (string) ($row['bypass_policy'] ?? 'allow'))) }}</td>
                                        <td>{{ (int) ($row['closed_count_sessions'] ?? 0) }}</td>
                                        <td>
                                            <span class="badge {{ !empty($row['lot_ready']) ? 'badge-light-success' : 'badge-light-warning' }}">
                                                {{ !empty($row['lot_ready']) ? __('lang_v1.ready') : __('lang_v1.attention_required') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ !empty($row['strict_ready']) ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ !empty($row['strict_ready']) ? __('lang_v1.ready') : __('lang_v1.not_ready') }}
                                            </span>
                                        </td>
                                        <td class="text-gray-700">{{ $row['strict_ready_reason'] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-8">@lang('lang_v1.no_reconciliation_data')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-6 mb-6">
                <div class="col-12 col-xxl-6">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.recent_warehouse_documents')</h3></div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    <thead><tr class="fw-bold text-gray-800"><th>@lang('sale.ref_no')</th><th>@lang('lang_v1.type')</th><th>@lang('lang_v1.status')</th><th>@lang('lang_v1.vas_sync')</th><th class="text-end">@lang('messages.action')</th></tr></thead>
                                    <tbody>
                                        @forelse($recentDocuments as $document)
                                            <tr>
                                                <td class="fw-semibold text-gray-900">{{ $document->document_no }}</td>
                                                <td>{{ ucwords(str_replace('_', ' ', $document->document_type)) }}</td>
                                                <td>{{ $document->status }}</td>
                                                <td>{{ $document->sync_status }}</td>
                                                <td class="text-end">
                                                    @if(in_array($document->sync_status, ['pending_sync', 'sync_error', 'reconcile_error']))
                                                        <form method="POST" action="{{ route('storage-manager.api.vas-retry') }}">
                                                            @csrf
                                                            <input type="hidden" name="document_id" value="{{ $document->id }}">
                                                            <button type="submit" class="btn btn-sm btn-light-primary">@lang('lang_v1.retry_sync')</button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted fs-8">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-8">@lang('lang_v1.no_warehouse_documents_yet')</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xxl-6">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.recent_movement_events')</h3></div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    <thead><tr class="fw-bold text-gray-800"><th>@lang('lang_v1.source')</th><th>@lang('lang_v1.type')</th><th>@lang('lang_v1.reason')</th><th>@lang('sale.qty')</th><th>@lang('lang_v1.status')</th></tr></thead>
                                    <tbody>
                                        @forelse($movementRows as $row)
                                            <tr>
                                                <td class="fw-semibold text-gray-900">{{ $row->source_type ?? '—' }}</td>
                                                <td>{{ $row->movement_type }}</td>
                                                <td>{{ $row->reason_code ?? '—' }}</td>
                                                <td>{{ @format_quantity($row->quantity) }}</td>
                                                <td>{{ trim(collect([$row->from_status, $row->to_status])->filter()->implode(' -> ')) ?: '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-8">@lang('lang_v1.no_movement_events_yet')</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-6 mb-6">
                <div class="col-12 col-xxl-6">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.bypass_events')</h3></div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    <thead><tr class="fw-bold text-gray-800"><th>@lang('lang_v1.type')</th><th>@lang('business.location')</th><th>@lang('sale.ref_no')</th><th>@lang('lang_v1.message')</th><th>@lang('lang_v1.date')</th></tr></thead>
                                    <tbody>
                                        @forelse(($dashboard['bypassRows'] ?? collect()) as $row)
                                            <tr>
                                                <td class="fw-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', (string) ($row['event_type'] ?? '-'))) }}</td>
                                                <td>{{ $row['location_name'] ?? '-' }}</td>
                                                <td>{{ $row['reference'] ?? '-' }}</td>
                                                <td class="text-gray-700">{{ $row['details'] ?? '-' }}</td>
                                                <td>{{ $row['event_date'] ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-8">@lang('lang_v1.no_bypass_events_detected')</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xxl-6">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.planning_advisories')</h3></div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    <thead><tr class="fw-bold text-gray-800"><th>@lang('product.product')</th><th>@lang('lang_v1.type')</th><th>@lang('lang_v1.source_slot')</th><th>@lang('lang_v1.destination_slot')</th><th>@lang('lang_v1.recommended_qty')</th><th>@lang('lang_v1.external_shortage_qty')</th><th class="text-end">@lang('messages.action')</th></tr></thead>
                                    <tbody>
                                        @forelse(($dashboard['planningRows'] ?? collect()) as $row)
                                            <tr>
                                                <td class="fw-semibold text-gray-900">{{ $row['product_label'] ?? '-' }}<div class="text-muted fs-8">{{ $row['sku'] ?? '-' }}</div></td>
                                                <td>{{ ucwords(str_replace('_', ' ', (string) ($row['advisory_type'] ?? '-'))) }}</td>
                                                <td>{{ $row['source_label'] ?? '-' }}</td>
                                                <td>{{ $row['destination_label'] ?? '-' }}</td>
                                                <td>{{ @format_quantity($row['recommended_qty'] ?? 0) }}</td>
                                                <td>{{ @format_quantity($row['external_shortage_qty'] ?? 0) }}</td>
                                                <td class="text-end">
                                                    @if(($row['advisory_type'] ?? '') === 'purchasing_review')
                                                        <a href="{{ route('storage-manager.planning.index', ['location_id' => $row['location_id'] ?? 0]) }}" class="btn btn-sm btn-light-primary">@lang('messages.view')</a>
                                                    @else
                                                        <span class="text-muted fs-8">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center text-muted py-8">@lang('lang_v1.no_planning_advisories')</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.recent_sync_log')</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead><tr class="fw-bold text-gray-800"><th>@lang('lang_v1.system')</th><th>@lang('lang_v1.action')</th><th>@lang('lang_v1.status')</th><th>@lang('lang_v1.message')</th><th>@lang('lang_v1.date')</th></tr></thead>
                            <tbody>
                                @forelse($recentSyncLogs as $log)
                                    <tr>
                                        <td>{{ strtoupper($log->linked_system) }}</td>
                                        <td>{{ $log->action }}</td>
                                        <td>{{ $log->status }}</td>
                                        <td class="text-gray-700">{{ $log->message ?: '—' }}</td>
                                        <td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-8">@lang('lang_v1.no_sync_logs_yet')</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
