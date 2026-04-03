@extends('layouts.app')

@section('title', __('lang_v1.damage_quarantine'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{ $document->document_no ?? __('lang_v1.damage_quarantine') }}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1"><li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li><li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li><li class="breadcrumb-item text-muted">@lang('lang_v1.damage_quarantine')</li></ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3"><a href="{{ route('storage-manager.damage.index') }}" class="btn btn-sm btn-light">@lang('messages.back')</a><a href="{{ route('storage-manager.control-tower.index') }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.control_tower')</a></div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @if(session('status'))<div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-6">{{ session('status.msg') }}</div>@endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('business.location')</div><div class="fs-4 fw-bold text-gray-900">{{ data_get($document->meta, 'location_name', '#' . $document->location_id) }}</div><div class="text-muted fs-7">{{ $document->workflow_state ?? '-' }}</div></div></div></div>
                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('sale.qty')</div><div class="fs-4 fw-bold text-gray-900">{{ format_quantity_value($document->lines->sum('executed_qty')) }}</div><div class="text-muted fs-7">Approval {{ $document->approval_status ?? 'not_required' }}</div></div></div></div>
                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">Sync Status</div><div class="fs-4 fw-bold text-gray-900">{{ $document->sync_status ?? 'not_required' }}</div><div class="text-muted fs-7">{{ $document->source_ref ?? 'No source adjustment yet' }}</div></div></div></div>
                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">Notes</div><div class="fw-semibold text-gray-900">{{ $document->notes ?: '—' }}</div></div></div></div>
            </div>

            <div class="card card-flush mb-6"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">Reported Lines</h3></div><div class="card-body pt-0"><div class="table-responsive"><table class="table table-row-dashed align-middle"><thead><tr class="fw-bold text-gray-800"><th>@lang('product.product')</th><th>@lang('product.sku')</th><th>@lang('sale.qty')</th><th>@lang('lang_v1.source_slot')</th><th>@lang('lang_v1.quarantine_slot')</th><th>Lot / Expiry</th><th>@lang('lang_v1.reason_code')</th><th>Result</th></tr></thead><tbody>@forelse($lineRows as $line)<tr><td class="fw-semibold text-gray-900">{{ $line['product_label'] ?? '-' }}</td><td>{{ $line['sku'] ?? '-' }}</td><td>{{ format_quantity_value($line['qty'] ?? 0) }}</td><td>{{ $line['from_slot_label'] ?? '-' }}</td><td>{{ $line['quarantine_slot_label'] ?? '-' }}</td><td>{{ $line['lot_number'] ?? '—' }}<div class="text-muted fs-8">{{ $line['expiry_date'] ?? 'No expiry' }}</div></td><td>{{ $line['reason_code'] ?? '—' }}</td><td><span class="badge badge-light-primary">{{ $line['result_status'] ?? '-' }}</span></td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-8">No reported lines.</td></tr>@endforelse</tbody></table></div></div></div>

            <div class="row g-5">
                <div class="col-xl-8">
                    @if(auth()->user()->can('storage_manager.approve') && !in_array((string) ($document->status ?? ''), ['closed', 'completed', 'cancelled'], true))
                        <form method="POST" action="{{ route('storage-manager.damage.resolve', $document->id) }}">
                            @csrf
                            <div class="card card-flush">
                                <div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.resolve_damage')</h3><div class="card-toolbar"><button type="submit" class="btn btn-sm btn-danger">Save Resolution</button></div></div>
                                <div class="card-body pt-0">
                                    <div class="row g-5 mb-6"><div class="col-md-4"><label class="form-label fw-semibold">@lang('lang_v1.resolve_damage')</label><select name="resolution_action" class="form-select form-select-solid"><option value="dispose">@lang('lang_v1.dispose_damage')</option><option value="release">@lang('lang_v1.release_back')</option></select></div><div class="col-md-8"><label class="form-label fw-semibold">Notes</label><input type="text" name="resolution_notes" class="form-control form-control-solid" value="{{ old('resolution_notes') }}"></div></div>
                                    <div class="table-responsive"><table class="table table-row-dashed align-middle"><thead><tr class="fw-bold text-gray-800"><th>@lang('product.product')</th><th>@lang('sale.qty')</th><th>Release Slot</th></tr></thead><tbody>@foreach($lineRows as $line)<tr><td>{{ $line['product_label'] ?? '-' }}</td><td>{{ format_quantity_value($line['qty'] ?? 0) }}</td><td><select name="lines[{{ $line['id'] }}][release_slot_id]" class="form-select form-select-sm form-select-solid"><option value="">Use original slot</option>@foreach($releaseSlotOptions as $slotId => $slotLabel)<option value="{{ $slotId }}">{{ $slotLabel }}</option>@endforeach</select></td></tr>@endforeach</tbody></table></div>
                                    <div class="alert alert-warning mt-4 mb-0">Disposing damage will post a real stock adjustment and sync to VAS when enabled. Releasing damage moves the stock back to its original warehouse status without changing accounting stock.</div>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
                <div class="col-xl-4"><div class="card card-flush"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">Approval Trail</h3></div><div class="card-body pt-0">@forelse($approvals as $approval)<div class="border border-gray-200 rounded p-4 mb-4"><div class="fw-semibold text-gray-900">{{ $approval->approval_type }}</div><div class="text-muted fs-8 mb-2">Qty {{ format_quantity_value($approval->threshold_value ?? 0) }}</div><span class="badge badge-light-{{ $approval->status === 'approved' ? 'success' : 'warning' }}">{{ $approval->status }}</span><div class="text-gray-700 fs-7 mt-2">{{ $approval->notes ?: '—' }}</div></div>@empty<div class="text-muted py-6">No approval records yet.</div>@endforelse</div></div></div>
            </div>
        </div>
    </div>
</div>
@endsection
