@extends('layouts.app')

@section('title', 'Putaway Document')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Putaway Document
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.putaway_document')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.putaway.index') }}" class="btn btn-sm btn-light">@lang('messages.back')</a>
                <a href="{{ route('storage-manager.inbound.index') }}" class="btn btn-sm btn-light-primary">Inbound Receiving</a>
                @if(auth()->user()->can('storage_manager.operate') && in_array((string) ($document->status ?? ''), ['closed', 'completed'], true))
                    <form method="POST" action="{{ route('storage-manager.putaway.reopen', $document->id) }}" class="d-inline-block">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-light-danger"
                                {{ empty($sourceSummary['can_reopen']) ? 'disabled' : '' }}
                                @if(!empty($sourceSummary['reopen_reason'])) title="{{ $sourceSummary['reopen_reason'] }}" @endif
                                onclick="return confirm('Reverse this putaway and reopen destination selection?');">
                            Reverse Putaway
                        </button>
                    </form>
                @endif
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

            @if(!empty($sourceSummary['reopen_reason']) && auth()->user()->can('storage_manager.operate') && in_array((string) ($document->status ?? ''), ['closed', 'completed'], true))
                <div class="alert alert-warning mb-6">
                    {{ $sourceSummary['reopen_reason'] }}
                </div>
            @endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-lg-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('sale.ref_no')</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $document->document_no ?? '—' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['source_ref'] ?? '—' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('business.location')</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $sourceSummary['location_name'] ?? '—' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['workflow_state'] ?? ($document->workflow_state ?? '—') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">Sync Status</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $sourceSummary['sync_status'] ?? $document->sync_status ?? 'not_required' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['status'] ?? $document->status ?? '—' }}</div>
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
                        <div class="col-md-3">
                            <div class="text-gray-500 fs-7">Receipt Document</div>
                            <div class="fw-semibold text-gray-900">{{ $sourceSummary['receipt_document_no'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-gray-500 fs-7">Parent Document</div>
                            <div class="fw-semibold text-gray-900">{{ $parentDocument->document_no ?? $parentDocument->ref_no ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-gray-500 fs-7">Workflow State</div>
                            <div class="fw-semibold text-gray-900">{{ $sourceSummary['workflow_state'] ?? $document->workflow_state ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-gray-500 fs-7">Sync Status</div>
                            <div class="fw-semibold text-gray-900">{{ $sourceSummary['sync_status'] ?? $document->sync_status ?? 'not_required' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->can('storage_manager.operate') && !in_array((string) ($document->status ?? ''), ['closed', 'completed'], true))
                <form method="POST" action="{{ route('storage-manager.putaway.complete', $document->id) }}">
                    @csrf
                    <div class="card card-flush">
                        <div class="card-header pt-6">
                            <h3 class="card-title fw-bold text-gray-900">Putaway Lines</h3>
                            <div class="card-toolbar">
                                <button type="submit" class="btn btn-sm btn-primary">Complete Putaway</button>
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
                                            <th>From Slot</th>
                                            <th>From Area</th>
                                            <th>Suggested Slot</th>
                                            <th>Selected Slot</th>
                                            <th>Lot Number</th>
                                            <th>Expiry Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($lineRows as $line)
                                            <tr>
                                                <td class="fw-semibold text-gray-900">{{ $line['product_label'] ?? '—' }}</td>
                                                <td>{{ $line['sku'] ?? '—' }}</td>
                                                <td>{{ format_quantity_value($line['qty'] ?? 0) }}</td>
                                                <td>{{ $line['from_slot_label'] ?? '—' }}</td>
                                                <td>{{ $line['from_area_label'] ?? '—' }}</td>
                                                <td>{{ $line['suggested_slot_label'] ?? '—' }}</td>
                                                <td>
                                                    <select name="lines[{{ $line['id'] }}][destination_slot_id]" class="form-select form-select-sm form-select-solid">
                                                        <option value="">Select</option>
                                                        @foreach($slotOptions as $slotId => $slotLabel)
                                                            <option value="{{ $slotId }}" @selected((int) ($line['selected_slot_id'] ?? 0) === (int) $slotId)>{{ $slotLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>{{ $line['lot_number'] ?? '—' }}</td>
                                                <td>{{ !empty($line['expiry_date']) ? \Illuminate\Support\Carbon::parse($line['expiry_date'])->format('Y-m-d') : '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-8">No putaway lines.</td>
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
                    Putaway already closed.
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
                                            <th>From Slot</th>
                                            <th>From Area</th>
                                            <th>Selected Slot</th>
                                            <th>Lot Number</th>
                                            <th>Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lineRows as $line)
                                        <tr>
                                            <td class="fw-semibold text-gray-900">{{ $line['product_label'] ?? '—' }}</td>
                                            <td>{{ $line['sku'] ?? '—' }}</td>
                                            <td>{{ format_quantity_value($line['qty'] ?? 0) }}</td>
                                            <td>{{ $line['from_slot_label'] ?? '—' }}</td>
                                            <td>{{ $line['from_area_label'] ?? '—' }}</td>
                                            <td>{{ $line['selected_slot_id'] ?? '—' }}</td>
                                            <td>{{ $line['lot_number'] ?? '—' }}</td>
                                            <td>{{ !empty($line['expiry_date']) ? \Illuminate\Support\Carbon::parse($line['expiry_date'])->format('Y-m-d') : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-8">No putaway lines.</td>
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
