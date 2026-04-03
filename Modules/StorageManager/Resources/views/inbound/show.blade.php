@extends('layouts.app')

@section('title', 'Inbound Receipt')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Inbound Receipt
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.inbound_receipt')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.inbound.index') }}" class="btn btn-sm btn-light">@lang('messages.back')</a>
                <a href="{{ route('storage-manager.putaway.index') }}" class="btn btn-sm btn-light-primary">Putaway Queue</a>
                @if(auth()->user()->can('storage_manager.operate') && in_array((string) ($document->status ?? ''), ['completed', 'closed'], true))
                    <form method="POST" action="{{ route('storage-manager.inbound.reopen', $document->id) }}" class="d-inline-block">
                        @csrf
                        <button type="submit"
                                class="btn btn-sm btn-light-danger"
                                {{ empty($sourceSummary['can_reopen']) ? 'disabled' : '' }}
                                @if(!empty($sourceSummary['reopen_reason'])) title="{{ $sourceSummary['reopen_reason'] }}" @endif
                                onclick="return confirm('Reverse this receipt and reopen receiving lines for editing?');">
                            Reverse Receipt
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

            @if(!empty($sourceSummary['reopen_reason']) && auth()->user()->can('storage_manager.operate') && in_array((string) ($document->status ?? ''), ['completed', 'closed'], true))
                <div class="alert alert-warning mb-6">
                    {{ $sourceSummary['reopen_reason'] }}
                </div>
            @endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-lg-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.source')</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $sourceSummary['source_ref'] ?? $document->document_no ?? '—' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['source_type'] ?? $document->document_type ?? '—' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('business.location')</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $sourceSummary['location_name'] ?? '—' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['execution_mode'] ?? 'off' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">Sync Status</div>
                            <div class="fs-4 fw-bold text-gray-900">{{ $document->sync_status ?? 'not_required' }}</div>
                            <div class="text-muted fs-7">{{ $sourceSummary['status'] ?? '—' }}</div>
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
                            <div class="text-gray-500 fs-7">@lang('purchase.supplier')</div>
                            <div class="fw-semibold text-gray-900">{{ $sourceSummary['supplier_name'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-gray-500 fs-7">@lang('sale.date')</div>
                            <div class="fw-semibold text-gray-900">
                                {{ !empty($sourceSummary['transaction_date']) ? \Illuminate\Support\Carbon::parse($sourceSummary['transaction_date'])->format('Y-m-d') : '—' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-gray-500 fs-7">Planning</div>
                            <div class="fw-semibold text-gray-900">
                                <span class="badge {{ !empty($sourceSummary['planning_only']) ? 'badge-light-info' : 'badge-light-success' }}">
                                    {{ !empty($sourceSummary['planning_only']) ? 'Planning Only' : 'Execution' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-5 mt-1">
                        <div class="col-md-4">
                            <div class="text-gray-500 fs-7">Purchase Permission</div>
                            <div class="fw-semibold text-gray-900">
                                <span class="badge {{ !empty($sourceSummary['requires_purchase_permission']) ? 'badge-light-warning' : 'badge-light-success' }}">
                                    {{ !empty($sourceSummary['requires_purchase_permission']) ? 'Required' : 'Not required' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-gray-500 fs-7">Putaway Document</div>
                            <div class="fw-semibold text-gray-900">
                                {{ !empty($sourceSummary['putaway_document_id']) ? $sourceSummary['putaway_document_id'] : __('lang_v1.none') }}
                                @if(!empty($sourceSummary['putaway_document_id']))
                                    <div class="mt-2">
                                        <a href="{{ route('storage-manager.putaway.show', $sourceSummary['putaway_document_id']) }}" class="btn btn-sm btn-light-primary">
                                            Open Putaway
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-gray-500 fs-7">Can Confirm</div>
                            <div class="fw-semibold text-gray-900">
                                <span class="badge {{ !empty($sourceSummary['can_confirm']) ? 'badge-light-success' : 'badge-light-secondary' }}">
                                    {{ !empty($sourceSummary['can_confirm']) ? 'Yes' : 'No' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($sourceSummary['can_confirm']))
                <form method="POST" action="{{ route('storage-manager.inbound.confirm', $document->id) }}">
                    @csrf
                    <div class="card card-flush">
                        <div class="card-header pt-6">
                            <h3 class="card-title fw-bold text-gray-900">Receiving Lines</h3>
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
                                            <th>Lot Number</th>
                                            <th>Expiry Date</th>
                                            <th>Staging Area</th>
                                            <th>Staging Slot</th>
                                            <th>Destination Hint</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($lineRows as $line)
                                            <tr>
                                                <td class="fw-semibold text-gray-900">
                                                    {{ $line['product_label'] ?? '—' }}
                                                    <div class="text-muted fs-8">{{ $line['source_status'] ?? '—' }}</div>
                                                </td>
                                                <td>{{ $line['sku'] ?? '—' }}</td>
                                                <td>
                                                    <input type="text" name="lines[{{ $line['id'] }}][executed_qty]" class="form-control form-control-sm form-control-solid" value="{{ $line['executed_qty'] ?? $line['expected_qty'] ?? 0 }}">
                                                </td>
                                                <td>
                                                    <input type="text" name="lines[{{ $line['id'] }}][lot_number]" class="form-control form-control-sm form-control-solid" value="{{ $line['lot_number'] ?? '' }}">
                                                </td>
                                                <td>
                                                    <div class="position-relative">
                                                        <i class="ki-duotone ki-calendar-8 fs-3 position-absolute top-50 translate-middle-y ms-4 text-gray-500">
                                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                                            <span class="path4"></span><span class="path5"></span><span class="path6"></span>
                                                        </i>
                                                        <input type="text"
                                                               name="lines[{{ $line['id'] }}][expiry_date]"
                                                               class="form-control form-control-sm form-control-solid ps-12 js-storage-expiry-datepicker"
                                                               value="{{ $line['expiry_date'] ?? '' }}"
                                                               data-date-format="Y-m-d"
                                                               autocomplete="off"
                                                               placeholder="YYYY-MM-DD">
                                                    </div>
                                                </td>
                                                <td>{{ $line['staging_area_label'] ?? '—' }}</td>
                                                <td>
                                                    <select name="lines[{{ $line['id'] }}][staging_slot_id]" class="form-select form-select-sm form-select-solid">
                                                        <option value="">Select</option>
                                                        @foreach($stagingSlotOptions as $slotId => $slotLabel)
                                                            <option value="{{ $slotId }}" @selected((int) ($line['staging_slot_id'] ?? 0) === (int) $slotId)>{{ $slotLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>{{ $line['destination_hint'] ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-8">No receiving lines.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                @if(!empty($sourceSummary['planning_only']))
                    <div class="alert alert-info">
                        This purchase order is planning-only in this phase. Inbound confirmation and putaway become available after creating/receiving the purchase from this PO.
                    </div>
                @else
                    <div class="alert alert-info">
                        Receipt not ready for confirmation.
                    </div>
                @endif
                <div class="card card-flush">
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead>
                                    <tr class="fw-bold text-gray-800">
                                        <th>@lang('product.product')</th>
                                        <th>@lang('product.sku')</th>
                                        <th>@lang('sale.qty')</th>
                                            <th>Lot Number</th>
                                            <th>Expiry Date</th>
                                            <th>Staging Area</th>
                                            <th>Staging Slot</th>
                                            <th>Destination Hint</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lineRows as $line)
                                        <tr>
                                            <td class="fw-semibold text-gray-900">{{ $line['product_label'] ?? '—' }}</td>
                                            <td>{{ $line['sku'] ?? '—' }}</td>
                                            <td>{{ format_quantity_value($line['expected_qty'] ?? 0) }}</td>
                                            <td>{{ $line['lot_number'] ?? '—' }}</td>
                                            <td>{{ !empty($line['expiry_date']) ? \Illuminate\Support\Carbon::parse($line['expiry_date'])->format('Y-m-d') : '—' }}</td>
                                            <td>{{ $line['staging_area_label'] ?? '—' }}</td>
                                            <td>{{ $line['staging_slot_id'] ?? '—' }}</td>
                                            <td>{{ $line['destination_hint'] ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-8">No receiving lines.</td>
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

@section('javascript')
<script>
    (function () {
        const selector = '.js-storage-expiry-datepicker';
        const inputs = document.querySelectorAll(selector);
        if (!inputs.length) {
            return;
        }

        if (typeof window.flatpickr === 'function') {
            inputs.forEach(function (input) {
                if (input.dataset.pickerReady === '1') {
                    return;
                }

                window.flatpickr(input, {
                    altInput: true,
                    altFormat: 'd M, Y',
                    dateFormat: input.dataset.dateFormat || 'Y-m-d',
                    allowInput: true
                });

                input.dataset.pickerReady = '1';
            });

            return;
        }

        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.datepicker === 'function') {
            window.jQuery(selector).datepicker({
                autoclose: true,
                todayHighlight: true,
                format: 'yyyy-mm-dd'
            });
        }
    })();
</script>
@endsection
