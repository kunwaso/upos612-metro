@extends('layouts.app')

@section('title', 'Putaway Queue')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Putaway Queue
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Putaway Queue</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.inbound.index') }}" class="btn btn-sm btn-light">Inbound Receiving</a>
                <a href="{{ route('storage-manager.index') }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.warehouse_map')</a>
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
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.open_documents')</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($queueSummary['open_documents'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.open_tasks')</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($queueSummary['open_tasks'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('sale.qty')</div>
                            <div class="fs-2hx fw-bold">{{ format_quantity_value($queueSummary['queued_qty'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.putaway.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                            <option value="0">@lang('messages.all')</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected((int) $locationId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted fs-7">Putaway task queue for staged inbound inventory</span>
                    </form>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">Putaway Documents</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="fw-bold text-gray-800">
                                    <th>@lang('sale.ref_no')</th>
                                    <th>Parent Document</th>
                                    <th>Source</th>
                                    <th>@lang('business.location')</th>
                                    <th>@lang('sale.qty')</th>
                                    <th>Workflow State</th>
                                    <th>Sync Status</th>
                                    <th class="text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row['document_no'] }}</td>
                                        <td>{{ $row['parent_document_no'] ?? '—' }}</td>
                                        <td>{{ $row['source_ref'] ?? '—' }}</td>
                                        <td>{{ $row['location_name'] ?? '—' }}</td>
                                        <td>{{ format_quantity_value($row['queued_qty'] ?? 0) }} <span class="text-muted fs-8">({{ (int) ($row['line_count'] ?? 0) }} lines)</span></td>
                                        <td>{{ $row['workflow_state'] ?? '—' }}</td>
                                        <td>
                                            <span class="badge {{ in_array((string) ($row['sync_status'] ?? ''), ['sync_error', 'reconcile_error']) ? 'badge-light-danger' : 'badge-light-info' }}">
                                                {{ $row['sync_status'] ?? 'not_required' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('storage-manager.putaway.show', $row['id']) }}" class="btn btn-sm btn-light-primary">@lang('messages.view')</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-8">No putaway documents.</td>
                                    </tr>
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
