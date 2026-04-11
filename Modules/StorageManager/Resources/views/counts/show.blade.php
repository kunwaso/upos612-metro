@extends('layouts.app')

@section('title', __('lang_v1.cycle_count_workbench'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <x-storagemanager::storage-toolbar
        :title="$storageToolbarTitle"
        :breadcrumbs="$storageToolbarBreadcrumbs"
        :map-location-id="$storageToolbarLocationId ?? null"
    >
        <x-slot name="contextActions">
                <a href="{{ route('storage-manager.counts.index') }}" class="btn btn-sm btn-light">@lang('messages.back')</a>
        </x-slot>
    </x-storagemanager::storage-toolbar>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @if(session('status'))<div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-6">{{ session('status.msg') }}</div>@endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('business.location')</div><div class="fs-4 fw-bold text-gray-900">{{ data_get($session->meta, 'location_name', '#' . $session->location_id) }}</div><div class="text-muted fs-7">{{ optional($session->area)->name ?: 'All enabled areas' }}</div></div></div></div>
                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.freeze_mode')</div><div class="fs-4 fw-bold text-gray-900">{{ ucfirst($session->freeze_mode ?? 'soft') }}</div><div class="text-muted fs-7">@lang('lang_v1.blind_count'): {{ !empty($session->blind_count) ? 'Yes' : 'No' }}</div></div></div></div>
                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">Line Count</div><div class="fs-4 fw-bold text-gray-900">{{ $session->lines->count() }}</div><div class="text-muted fs-7">Status {{ $session->status ?? '-' }}</div></div></div></div>
                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.variance_qty')</div><div class="fs-4 fw-bold text-gray-900">{{ format_quantity_value($session->lines->sum('variance_qty')) }}</div><div class="text-muted fs-7">Started {{ optional($session->started_at)->format('Y-m-d H:i') ?: '—' }}</div></div></div></div>
            </div>

            @php $negativeReviews = collect($lineRows)->where('status', 'review')->filter(fn($line) => ($line['variance_qty'] ?? 0) < 0); $positiveReviews = collect($lineRows)->where('status', 'review')->filter(fn($line) => ($line['variance_qty'] ?? 0) > 0); @endphp
            @if($positiveReviews->isNotEmpty())<div class="alert alert-warning mb-6">Positive count gains are intentionally held in manual review. This phase does not auto-post gain adjustments because the current source stock-adjustment flow is decrease-only.</div>@endif

            @if(auth()->user()->can('storage_manager.count') && !in_array((string) ($session->status ?? ''), ['closed', 'cancelled'], true))
                <form method="POST" action="{{ route('storage-manager.counts.submit', $session->id) }}">
                    @csrf
                    <div class="card card-flush mb-6"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.cycle_count_workbench')</h3><div class="card-toolbar"><button type="submit" class="btn btn-sm btn-primary">Submit Count</button></div></div><div class="card-body pt-0"><div class="table-responsive"><table class="table table-row-dashed align-middle"><thead><tr class="fw-bold text-gray-800"><th>@lang('lang_v1.source_slot')</th><th>@lang('product.product')</th><th>@lang('product.sku')</th><th>Status</th><th>System Qty</th><th>@lang('lang_v1.counted_qty')</th><th>@lang('lang_v1.reason_code')</th><th>@lang('lang_v1.variance_qty')</th></tr></thead><tbody>@forelse($lineRows as $line)<tr><td>{{ $line['slot_label'] ?? '-' }}</td><td class="fw-semibold text-gray-900">{{ $line['product_label'] ?? '-' }}<div class="text-muted fs-8">{{ $line['lot_number'] ?? '—' }}</div></td><td>{{ $line['sku'] ?? '-' }}</td><td>{{ $line['inventory_status'] ?? '-' }}</td><td>{{ $line['system_qty'] !== null ? format_quantity_value($line['system_qty']) : 'Blind' }}</td><td><input type="number" step="0.0001" min="0" name="lines[{{ $line['id'] }}][counted_qty]" class="form-control form-control-sm form-control-solid" value="{{ old('lines.' . $line['id'] . '.counted_qty', $line['counted_qty'] ?? $line['system_qty'] ?? 0) }}"></td><td><input type="text" name="lines[{{ $line['id'] }}][reason_code]" class="form-control form-control-sm form-control-solid" value="{{ old('lines.' . $line['id'] . '.reason_code', $line['reason_code'] ?? '') }}"></td><td><span class="badge badge-light-{{ ($line['variance_qty'] ?? 0) < 0 ? 'danger' : (($line['variance_qty'] ?? 0) > 0 ? 'warning' : 'success') }}">{{ format_quantity_value($line['variance_qty'] ?? 0) }}</span></td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-8">No count lines.</td></tr>@endforelse</tbody></table></div></div></div>
                </form>
            @endif

            @if(auth()->user()->can('storage_manager.approve') && $negativeReviews->isNotEmpty())
                <form method="POST" action="{{ route('storage-manager.counts.approve-shortages', $session->id) }}">
                    @csrf
                    <div class="card card-flush mb-6"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">Approve Shortages</h3><div class="card-toolbar"><button type="submit" class="btn btn-sm btn-danger">Approve Shortages</button></div></div><div class="card-body pt-0"><div class="mb-4 text-muted">This will post a real stock adjustment for negative variances only, then sync the resulting cycle-count document to VAS when enabled.</div><label class="form-label fw-semibold">Approval Notes</label><input type="text" name="approval_notes" class="form-control form-control-solid" value="{{ old('approval_notes') }}"></div></div>
                </form>
            @endif

            <div class="card card-flush"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">Approval Trail</h3></div><div class="card-body pt-0"><div class="table-responsive"><table class="table table-row-dashed align-middle"><thead><tr class="fw-bold text-gray-800"><th>ID</th><th>Type</th><th>Direction</th><th>@lang('sale.qty')</th><th>@lang('lang_v1.status')</th></tr></thead><tbody>@forelse($approvals as $approval)<tr><td>{{ $approval->id }}</td><td>{{ $approval->approval_type }}</td><td>{{ data_get($approval->payload, 'direction', 'review') }}</td><td>{{ format_quantity_value($approval->threshold_value ?? 0) }}</td><td><span class="badge badge-light-{{ $approval->status === 'approved' ? 'success' : 'warning' }}">{{ $approval->status }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-8">No approval entries yet.</td></tr>@endforelse</tbody></table></div></div></div>
        </div>
    </div>
</div>
@endsection
