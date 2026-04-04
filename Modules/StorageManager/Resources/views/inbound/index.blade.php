@extends('layouts.app')

@section('title', 'Inbound Receiving')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Inbound Receiving
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Inbound Receiving</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.index') }}" class="btn btn-sm btn-light">@lang('lang_v1.warehouse_map')</a>
                <a href="{{ route('storage-manager.putaway.index') }}" class="btn btn-sm btn-light-primary">Putaway Queue</a>
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
                            <div class="text-gray-500 fs-7">Enabled Locations</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($executionSummary['enabled_location_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('purchase.purchases')</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($executionSummary['purchase_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.purchase_order')</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($executionSummary['purchase_order_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.inbound.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                            <option value="0">@lang('messages.all')</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected((int) $locationId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted fs-7">Receiving and purchase order queue</span>
                    </form>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">Receipts Ready for Execution</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="fw-bold text-gray-800">
                                    <th>@lang('lang_v1.source')</th>
                                    <th>@lang('business.location')</th>
                                    <th>@lang('purchase.supplier')</th>
                                    <th>@lang('sale.date')</th>
                                    <th>@lang('sale.qty')</th>
                                    <th>@lang('lang_v1.execution_mode')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th class="text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchases as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row['source_ref'] ?? $row['source_id'] ?? '—' }}</td>
                                        <td>{{ $row['location_name'] ?? '—' }}</td>
                                        <td>{{ $row['supplier_name'] ?? '—' }}</td>
                                        <td>{{ !empty($row['transaction_date']) ? \Illuminate\Support\Carbon::parse($row['transaction_date'])->format('Y-m-d') : '—' }}</td>
                                        <td>{{ format_quantity_value($row['expected_qty'] ?? 0) }} <span class="text-muted fs-8">({{ (int) ($row['line_count'] ?? 0) }} lines)</span></td>
                                        <td>{{ ucwords(str_replace('_', ' ', (string) ($row['execution_mode'] ?? 'off'))) }}</td>
                                        <td>
                                            <span class="badge {{ !empty($row['ready_for_execution']) ? 'badge-light-success' : 'badge-light-warning' }}">
                                                {{ !empty($row['ready_for_execution']) ? 'Ready' : 'Attention Required' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if(!empty($row['can_open']))
                                                <a href="{{ route('storage-manager.inbound.show', ['sourceType' => $row['source_type'], 'sourceId' => $row['source_id']]) }}" class="btn btn-sm btn-light-primary">
                                                    @lang('messages.view')
                                                </a>
                                            @else
                                                <span class="text-muted fs-8">{{ $row['action_note'] ?? 'Not openable' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-8">No receipts ready.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">Purchase Orders</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="fw-bold text-gray-800">
                                    <th>@lang('lang_v1.source')</th>
                                    <th>@lang('business.location')</th>
                                    <th>@lang('purchase.supplier')</th>
                                    <th>@lang('sale.date')</th>
                                    <th>@lang('sale.qty')</th>
                                    <th>@lang('lang_v1.planning')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th class="text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseOrders as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row['source_ref'] ?? $row['source_id'] ?? '—' }}</td>
                                        <td>{{ $row['location_name'] ?? '—' }}</td>
                                        <td>{{ $row['supplier_name'] ?? '—' }}</td>
                                        <td>{{ !empty($row['transaction_date']) ? \Illuminate\Support\Carbon::parse($row['transaction_date'])->format('Y-m-d') : '—' }}</td>
                                        <td>{{ format_quantity_value($row['expected_qty'] ?? 0) }} <span class="text-muted fs-8">({{ (int) ($row['line_count'] ?? 0) }} lines)</span></td>
                                        <td>
                                            <span class="badge {{ !empty($row['has_open_generated_purchase']) ? 'badge-light-warning' : 'badge-light-info' }}">
                                                {{ !empty($row['has_open_generated_purchase']) ? 'In Progress' : 'Ready to Receive' }}
                                            </span>
                                            @if(!empty($row['receive_action_note']))
                                                <div class="text-muted fs-8 mt-1">{{ $row['receive_action_note'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ !empty($row['ready_for_execution']) ? 'badge-light-success' : 'badge-light-warning' }}">
                                                {{ !empty($row['ready_for_execution']) ? 'Ready' : 'Blocked' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if(!empty($row['can_open']))
                                                <form method="POST" action="{{ route('storage-manager.inbound.purchase-orders.start-receiving', $row['source_id']) }}" class="d-inline-block">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ !empty($row['has_open_generated_purchase']) ? 'btn-light-warning' : 'btn-light-primary' }}">
                                                        {{ $row['receive_action_label'] ?? 'Receive Goods' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted fs-8">{{ $row['action_note'] ?? 'Receiving unavailable' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-8">No purchase orders yet.</td>
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
