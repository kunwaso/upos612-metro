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
