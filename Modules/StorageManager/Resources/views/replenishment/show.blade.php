@extends('layouts.app')

@section('title', 'Replenishment Document')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Replenishment Document
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Replenishment Document</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.replenishment.index') }}" class="btn btn-sm btn-light">@lang('messages.back')</a>
                <a href="{{ route('storage-manager.transfers.index') }}" class="btn btn-sm btn-light-primary">Transfer Execution</a>
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
                <div class="col-lg-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('product.product')</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $sourceSummary['product_label'] ?? '-' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['sku'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('business.location')</div>
                            <div class="fs-4 fw-bold text-gray-900">Location #{{ $sourceSummary['location_id'] ?? $rule->location_id }}</div>
                            <div class="text-muted fs-7">{{ $rule->status ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">Source / Destination</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $sourceSummary['source_label'] ?? '-' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['destination_label'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('sale.qty')</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ format_quantity_value($sourceSummary['recommended_qty'] ?? 0) }}</div>
                            <div class="text-muted fs-7">Document {{ $document->document_no ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">Rule Details</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-5">
                        <div class="col-md-3">
                            <div class="text-gray-500 fs-7">Source Quantity</div>
                            <div class="fw-semibold text-gray-900">{{ format_quantity_value($sourceSummary['source_qty'] ?? 0) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-gray-500 fs-7">Destination Quantity</div>
                            <div class="fw-semibold text-gray-900">{{ format_quantity_value($sourceSummary['destination_qty'] ?? 0) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-gray-500 fs-7">Minimum Quantity</div>
                            <div class="fw-semibold text-gray-900">{{ format_quantity_value($sourceSummary['min_qty'] ?? 0) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-gray-500 fs-7">Maximum Quantity</div>
                            <div class="fw-semibold text-gray-900">{{ format_quantity_value($sourceSummary['max_qty'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->can('storage_manager.operate') && !in_array((string) ($document->status ?? ''), ['closed', 'completed', 'cancelled'], true))
                <form method="POST" action="{{ route('storage-manager.replenishment.complete', $rule->id) }}">
                    @csrf
                    <div class="card card-flush">
                        <div class="card-header pt-6">
                            <h3 class="card-title fw-bold text-gray-900">Replenishment Lines</h3>
                            <div class="card-toolbar">
                                <button type="submit" class="btn btn-sm btn-primary">Complete Replenishment</button>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    <thead>
                                        <tr class="fw-bold text-gray-800">
                                            <th>@lang('product.product')</th>
                                            <th>@lang('product.sku')</th>
                                            <th>@lang('sale.qty')</th>
                                            <th>Source Slot</th>
                                            <th>Destination Slot</th>
                                            <th>Source Qty</th>
                                            <th>Destination Qty</th>
                                            <th>Recommended</th>
                                            <th>Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($lineRows as $line)
                                            <tr>
                                                <td class="fw-semibold text-gray-900">{{ $line['product_label'] ?? '-' }}</td>
                                                <td>{{ $line['sku'] ?? '-' }}</td>
                                                <td>
                                                    <input type="number" step="0.0001" min="0" name="lines[{{ $line['id'] }}][executed_qty]" class="form-control form-control-sm form-control-solid" value="{{ old('lines.' . $line['id'] . '.executed_qty', $line['qty'] ?? 0) }}">
                                                </td>
                                                <td>
                                                    <select name="lines[{{ $line['id'] }}][source_slot_id]" class="form-select form-select-sm form-select-solid">
                                                        <option value="">Select</option>
                                                        @foreach($sourceSlotOptions as $slotId => $slotLabel)
                                                            <option value="{{ $slotId }}" @selected((string) old('lines.' . $line['id'] . '.source_slot_id', (string) ($line['source_slot_id'] ?? '')) === (string) $slotId)>{{ $slotLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="lines[{{ $line['id'] }}][destination_slot_id]" class="form-select form-select-sm form-select-solid">
                                                        <option value="">Select</option>
                                                        @foreach($destinationSlotOptions as $slotId => $slotLabel)
                                                            <option value="{{ $slotId }}" @selected((string) old('lines.' . $line['id'] . '.destination_slot_id', (string) ($line['destination_slot_id'] ?? '')) === (string) $slotId)>{{ $slotLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>{{ format_quantity_value($line['source_available_qty'] ?? 0) }}</td>
                                                <td>{{ format_quantity_value($line['destination_qty'] ?? 0) }}</td>
                                                <td>{{ format_quantity_value($line['recommended_qty'] ?? 0) }}</td>
                                                <td>
                                                    <span class="badge badge-light-{{ (string) ($line['result_status'] ?? '') === 'completed' ? 'success' : 'warning' }}">
                                                        {{ $line['result_status'] ?? '-' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-8">No replenishment lines.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="alert alert-info">
                    Replenishment is not ready for completion.
                </div>
                <div class="card card-flush">
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead>
                                    <tr class="fw-bold text-gray-800">
                                        <th>@lang('product.product')</th>
                                        <th>@lang('product.sku')</th>
                                        <th>@lang('sale.qty')</th>
                                        <th>Source Slot</th>
                                        <th>Destination Slot</th>
                                        <th>Source Qty</th>
                                        <th>Destination Qty</th>
                                        <th>Recommended</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lineRows as $line)
                                        <tr>
                                            <td class="fw-semibold text-gray-900">{{ $line['product_label'] ?? '-' }}</td>
                                            <td>{{ $line['sku'] ?? '-' }}</td>
                                            <td>{{ format_quantity_value($line['qty'] ?? 0) }}</td>
                                            <td>{{ $line['source_slot_label'] ?? '-' }}</td>
                                            <td>{{ $line['destination_slot_label'] ?? '-' }}</td>
                                            <td>{{ format_quantity_value($line['source_available_qty'] ?? 0) }}</td>
                                            <td>{{ format_quantity_value($line['destination_qty'] ?? 0) }}</td>
                                            <td>{{ format_quantity_value($line['recommended_qty'] ?? 0) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-8">No replenishment lines.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
