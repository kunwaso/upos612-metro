@extends('layouts.app')

@section('title', __('lang_v1.transfer_execution'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <x-storagemanager::storage-toolbar
        :title="$storageToolbarTitle"
        :breadcrumbs="$storageToolbarBreadcrumbs"
        :map-location-id="$storageToolbarLocationId ?? null"
    >
        <x-slot name="contextActions">
                <a href="{{ route('storage-manager.replenishment.index') }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.replenishment_queue')</a>
        </x-slot>
    </x-storagemanager::storage-toolbar>

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
                            <div class="text-gray-500 fs-7">Enabled Locations</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($dispatchSummary['enabled_location_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">Dispatch Queue</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($dispatchSummary['dispatch_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">Receipt Queue</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($dispatchSummary['receipt_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.transfers.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                            <option value="0">@lang('messages.all')</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected((int) $locationId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted fs-7">Transfer dispatch and receipt work queues</span>
                    </form>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">Dispatch Work Queue</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="fw-bold text-gray-800">
                                    <th>@lang('lang_v1.source')</th>
                                    <th>From Location</th>
                                    <th>To Location</th>
                                    <th>@lang('sale.date')</th>
                                    <th>@lang('sale.qty')</th>
                                    <th>@lang('lang_v1.execution_mode')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th>Document</th>
                                    <th class="text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dispatchRows as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row['source_ref'] ?? '-' }}</td>
                                        <td>{{ $row['source_location_name'] ?? '-' }}</td>
                                        <td>{{ $row['destination_location_name'] ?? '-' }}</td>
                                        <td>{{ !empty($row['transaction_date']) ? \Illuminate\Support\Carbon::parse($row['transaction_date'])->format('Y-m-d H:i') : '-' }}</td>
                                        <td>{{ format_quantity_value($row['expected_qty'] ?? 0) }} <span class="text-muted fs-8">({{ (int) ($row['line_count'] ?? 0) }} lines)</span></td>
                                        <td>{{ ucwords(str_replace('_', ' ', (string) ($row['execution_mode'] ?? 'off'))) }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ in_array((string) ($row['status'] ?? ''), ['in_transit', 'received'], true) ? 'success' : 'warning' }}">
                                                {{ $row['status'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(!empty($row['document_id']))
                                                <div class="fw-semibold text-gray-900">{{ $row['document_no'] ?? '-' }}</div>
                                                <div class="text-muted fs-8">{{ $row['document_status'] ?? 'not_required' }}</div>
                                            @else
                                                <span class="text-muted fs-8">Not generated</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if(!empty($row['source_id']))
                                                <a href="{{ route('storage-manager.transfers.dispatch.show', $row['source_id']) }}" class="btn btn-sm btn-light-primary">
                                                    @lang('messages.view')
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-8">No transfer dispatches.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">Receipt Work Queue</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="fw-bold text-gray-800">
                                    <th>@lang('lang_v1.source')</th>
                                    <th>From Location</th>
                                    <th>To Location</th>
                                    <th>@lang('sale.date')</th>
                                    <th>@lang('sale.qty')</th>
                                    <th>@lang('lang_v1.execution_mode')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th>Document</th>
                                    <th class="text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receiptRows as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row['source_ref'] ?? '-' }}</td>
                                        <td>{{ $row['source_location_name'] ?? '-' }}</td>
                                        <td>{{ $row['destination_location_name'] ?? '-' }}</td>
                                        <td>{{ !empty($row['transaction_date']) ? \Illuminate\Support\Carbon::parse($row['transaction_date'])->format('Y-m-d H:i') : '-' }}</td>
                                        <td>{{ format_quantity_value($row['expected_qty'] ?? 0) }} <span class="text-muted fs-8">({{ (int) ($row['line_count'] ?? 0) }} lines)</span></td>
                                        <td>{{ ucwords(str_replace('_', ' ', (string) ($row['execution_mode'] ?? 'off'))) }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ in_array((string) ($row['status'] ?? ''), ['received', 'completed'], true) ? 'success' : 'warning' }}">
                                                {{ $row['status'] ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(!empty($row['document_id']))
                                                <div class="fw-semibold text-gray-900">{{ $row['document_no'] ?? '-' }}</div>
                                                <div class="text-muted fs-8">{{ $row['document_status'] ?? 'not_required' }}</div>
                                            @else
                                                <span class="text-muted fs-8">Not generated</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if(!empty($row['source_id']))
                                                <a href="{{ route('storage-manager.transfers.receipts.show', $row['source_id']) }}" class="btn btn-sm btn-light-primary">
                                                    @lang('messages.view')
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-8">No transfer receipts.</td>
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
