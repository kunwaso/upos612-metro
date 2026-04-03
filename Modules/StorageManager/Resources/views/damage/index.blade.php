@extends('layouts.app')

@section('title', __('lang_v1.damage_quarantine'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">@lang('lang_v1.damage_quarantine')</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.damage_quarantine')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.control-tower.index') }}" class="btn btn-sm btn-light">@lang('lang_v1.control_tower')</a>
                <a href="{{ route('storage-manager.index') }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.warehouse_map')</a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @if(session('status'))
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-6">{{ session('status.msg') }}</div>
            @endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">Enabled Locations</div><div class="fs-2hx fw-bold">{{ (int) ($boardSummary['enabled_location_count'] ?? 0) }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">Open Documents</div><div class="fs-2hx fw-bold">{{ (int) ($boardSummary['open_documents'] ?? 0) }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">Pending Approvals</div><div class="fs-2hx fw-bold">{{ (int) ($boardSummary['pending_approvals'] ?? 0) }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.quarantine_qty')</div><div class="fs-2hx fw-bold">{{ format_quantity_value($boardSummary['quarantine_qty'] ?? 0) }}</div></div></div></div>
            </div>

            <div class="card card-flush mb-6"><div class="card-body py-4">
                <form method="GET" action="{{ route('storage-manager.damage.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                    <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                    <select name="location_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                        <option value="0">@lang('messages.all')</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}" @selected((int) $locationId === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted fs-7">Available sellable stock can be quarantined here before disposal or release.</span>
                </form>
            </div></div>

            <div class="card card-flush mb-6">
                <div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.report_damage')</h3></div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead><tr class="fw-bold text-gray-800"><th>@lang('product.product')</th><th>@lang('product.sku')</th><th>@lang('lang_v1.source_slot')</th><th>@lang('sale.qty')</th><th colspan="3" class="text-end">@lang('messages.action')</th></tr></thead>
                            <tbody>
                                @forelse($availableBuckets as $bucket)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $bucket['product_label'] ?? '-' }}<div class="text-muted fs-8">{{ $bucket['inventory_status'] ?? 'available' }}</div></td>
                                        <td>{{ $bucket['sku'] ?? '-' }}@if(!empty($bucket['lot_number']))<div class="text-muted fs-8">Lot {{ $bucket['lot_number'] }}</div>@endif</td>
                                        <td>{{ $bucket['slot_label'] ?? '-' }}</td>
                                        <td>{{ format_quantity_value($bucket['qty_on_hand'] ?? 0) }}</td>
                                        <td colspan="3">
                                            @if((int) $locationId > 0)
                                                <form method="POST" action="{{ route('storage-manager.damage.store') }}" class="d-flex gap-2 flex-wrap justify-content-end">
                                                    @csrf
                                                    <input type="hidden" name="location_id" value="{{ $locationId }}">
                                                    <input type="hidden" name="source_slot_id" value="{{ $bucket['slot_id'] }}">
                                                    <input type="hidden" name="product_id" value="{{ $bucket['product_id'] }}">
                                                    <input type="hidden" name="variation_id" value="{{ $bucket['variation_id'] }}">
                                                    <input type="hidden" name="inventory_status" value="{{ $bucket['inventory_status'] }}">
                                                    <input type="hidden" name="lot_number" value="{{ $bucket['lot_number'] }}">
                                                    <input type="hidden" name="expiry_date" value="{{ $bucket['expiry_date'] }}">
                                                    <input type="number" step="0.0001" min="0.0001" max="{{ $bucket['qty_on_hand'] ?? 0 }}" name="quantity" class="form-control form-control-sm form-control-solid w-125px" placeholder="Qty" value="{{ $bucket['qty_on_hand'] ?? 0 }}">
                                                    <select name="quarantine_slot_id" class="form-select form-select-sm form-select-solid w-225px">
                                                        <option value="">@lang('lang_v1.quarantine_slot')</option>
                                                        @foreach($quarantineSlotOptions as $slotId => $slotLabel)
                                                            <option value="{{ $slotId }}">{{ $slotLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="reason_code" class="form-control form-control-sm form-control-solid w-175px" placeholder="@lang('lang_v1.reason_code')">
                                                    <button type="submit" class="btn btn-sm btn-light-danger">@lang('lang_v1.report_damage')</button>
                                                </form>
                                            @else
                                                <span class="text-muted fs-8">Select a location to report damage.</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-8">No available stock buckets are ready for damage reporting.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">Damage Documents</h3></div><div class="card-body pt-0"><div class="table-responsive"><table class="table table-row-dashed align-middle"><thead><tr class="fw-bold text-gray-800"><th>Document</th><th>@lang('business.location')</th><th>@lang('sale.qty')</th><th>@lang('lang_v1.status')</th><th>Approval</th><th>Sync</th><th class="text-end">@lang('messages.action')</th></tr></thead><tbody>@forelse($documentRows as $row)<tr><td class="fw-semibold text-gray-900">{{ $row['document_no'] ?? '-' }}</td><td>{{ $row['location_name'] ?? '-' }}</td><td>{{ format_quantity_value($row['reported_qty'] ?? 0) }}</td><td><span class="badge badge-light-primary">{{ $row['workflow_state'] ?? ($row['status'] ?? '-') }}</span></td><td>{{ $row['approval_status'] ?? 'not_required' }}</td><td>{{ $row['sync_status'] ?? 'not_required' }}</td><td class="text-end"><a href="{{ route('storage-manager.damage.show', $row['id']) }}" class="btn btn-sm btn-light-primary">@lang('messages.view')</a></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-8">No damage documents yet.</td></tr>@endforelse</tbody></table></div></div></div>

            <div class="card card-flush"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">Pending Damage Approvals</h3></div><div class="card-body pt-0"><div class="table-responsive"><table class="table table-row-dashed align-middle"><thead><tr class="fw-bold text-gray-800"><th>ID</th><th>Document</th><th>Type</th><th>@lang('sale.qty')</th><th>@lang('lang_v1.status')</th><th>Notes</th></tr></thead><tbody>@forelse($approvalRows as $approval)<tr><td>{{ $approval['id'] ?? '-' }}</td><td>{{ $approval['document_id'] ?? '-' }}</td><td>{{ $approval['approval_type'] ?? '-' }}</td><td>{{ format_quantity_value($approval['threshold_value'] ?? 0) }}</td><td><span class="badge badge-light-warning">{{ $approval['status'] ?? '-' }}</span></td><td>{{ $approval['notes'] ?? '—' }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-8">No pending damage approvals.</td></tr>@endforelse</tbody></table></div></div></div>
        </div>
    </div>
</div>
@endsection
