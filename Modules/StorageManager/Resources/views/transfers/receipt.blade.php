@extends('layouts.app')

@section('title', __('lang_v1.transfer_receipt'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <x-storagemanager::storage-toolbar
        :title="$storageToolbarTitle"
        :breadcrumbs="$storageToolbarBreadcrumbs"
        :map-location-id="$storageToolbarLocationId ?? null"
    >
        <x-slot name="contextActions">
                <a href="{{ route('storage-manager.transfers.index') }}" class="btn btn-sm btn-light">@lang('messages.back')</a>
                <a href="{{ route('storage-manager.transfers.dispatch.show', $document->source_id) }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.dispatch_workbench')</a>
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
                <div class="col-lg-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.source')</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $sourceSummary['source_ref'] ?? '-' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['source_status'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">From / To</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $sourceSummary['source_location_name'] ?? '-' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['destination_location_name'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('sale.date')</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ !empty($sourceSummary['transaction_date']) ? \Illuminate\Support\Carbon::parse($sourceSummary['transaction_date'])->format('Y-m-d H:i') : '-' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['execution_mode'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.status')</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $document->document_no ?? '-' }}</div>
                            <div class="text-muted fs-7">{{ $document->status ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">Source Document Details</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <div class="text-gray-500 fs-7">Dispatch Document</div>
                            <div class="fw-semibold text-gray-900">{{ $parentDocument->document_no ?? $parentDocument->ref_no ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-gray-500 fs-7">Execution Mode</div>
                            <div class="fw-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', (string) ($sourceSummary['execution_mode'] ?? 'off'))) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-gray-500 fs-7">Has Putaway Document</div>
                            <div class="fw-semibold text-gray-900">
                                <span class="badge {{ !empty($sourceSummary['has_putaway_document']) ? 'badge-light-success' : 'badge-light-secondary' }}">
                                    {{ !empty($sourceSummary['has_putaway_document']) ? 'Yes' : 'No' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if(!empty($sourceSummary['has_putaway_document']) && !empty($sourceSummary['putaway_document_id']))
                        <div class="row g-5 mt-1">
                            <div class="col-md-12">
                                <a href="{{ route('storage-manager.putaway.show', $sourceSummary['putaway_document_id']) }}" class="btn btn-sm btn-light-primary">
                                    Open Putaway Document
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if(auth()->user()->can('storage_manager.operate') && !empty($sourceSummary['can_confirm']))
                <form method="POST" action="{{ route('storage-manager.transfers.receipts.confirm', $document->id) }}">
                    @csrf
                    <div class="card card-flush">
                        <div class="card-header pt-6">
                            <h3 class="card-title fw-bold text-gray-900">Receipt Lines</h3>
                            <div class="card-toolbar">
                                <button type="submit" class="btn btn-sm btn-primary">Confirm Receipt</button>
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
                                            <th>Dispatch Slot</th>
                                            <th>Dispatch Area</th>
                                            <th>Staging Slot</th>
                                            <th>Lot Number</th>
                                            <th>Expiry Date</th>
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
                                                <td>{{ $line['dispatch_slot_label'] ?? '-' }}</td>
                                                <td>{{ $line['dispatch_area_label'] ?? '-' }}</td>
                                                <td>
                                                    <select name="lines[{{ $line['id'] }}][staging_slot_id]" class="form-select form-select-sm form-select-solid">
                                                        <option value="">Select</option>
                                                        @foreach($stagingSlotOptions as $slotId => $slotLabel)
                                                            <option value="{{ $slotId }}" @selected((string) old('lines.' . $line['id'] . '.staging_slot_id', (string) ($line['staging_slot_id'] ?? '')) === (string) $slotId)>{{ $slotLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>{{ $line['lot_number'] ?? '-' }}</td>
                                                <td>{{ !empty($line['expiry_date']) ? \Illuminate\Support\Carbon::parse($line['expiry_date'])->format('Y-m-d') : '-' }}</td>
                                                <td>
                                                    <span class="badge badge-light-{{ (string) ($line['result_status'] ?? '') === 'completed' ? 'success' : 'warning' }}">
                                                        {{ $line['result_status'] ?? '-' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-8">No receipt lines.</td>
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
                    Receipt is not ready for confirmation.
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
                                        <th>Dispatch Slot</th>
                                        <th>Dispatch Area</th>
                                        <th>Staging Slot</th>
                                        <th>Lot Number</th>
                                        <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lineRows as $line)
                                        <tr>
                                            <td class="fw-semibold text-gray-900">{{ $line['product_label'] ?? '-' }}</td>
                                            <td>{{ $line['sku'] ?? '-' }}</td>
                                            <td>{{ format_quantity_value($line['qty'] ?? 0) }}</td>
                                            <td>{{ $line['dispatch_slot_label'] ?? '-' }}</td>
                                            <td>{{ $line['dispatch_area_label'] ?? '-' }}</td>
                                            <td>{{ $line['staging_slot_label'] ?? '-' }}</td>
                                            <td>{{ $line['lot_number'] ?? '-' }}</td>
                                            <td>{{ !empty($line['expiry_date']) ? \Illuminate\Support\Carbon::parse($line['expiry_date'])->format('Y-m-d') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-8">No receipt lines.</td>
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
