@extends('layouts.app')

@section('title', __('lang_v1.cycle_count_sessions'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <x-storagemanager::storage-toolbar
        :title="$storageToolbarTitle"
        :breadcrumbs="$storageToolbarBreadcrumbs"
        :map-location-id="$storageToolbarLocationId ?? null"
    >
        <x-slot name="contextActions">
                <a href="{{ route('storage-manager.damage.index') }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.damage_quarantine')</a>
        </x-slot>
    </x-storagemanager::storage-toolbar>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @if(session('status'))<div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-6">{{ session('status.msg') }}</div>@endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">Enabled Locations</div><div class="fs-2hx fw-bold">{{ (int) ($boardSummary['enabled_location_count'] ?? 0) }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">Open Sessions</div><div class="fs-2hx fw-bold">{{ (int) ($boardSummary['open_sessions'] ?? 0) }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.pending_shortages')</div><div class="fs-2hx fw-bold">{{ (int) ($boardSummary['pending_shortages'] ?? 0) }}</div></div></div></div>
                <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.pending_gain_reviews')</div><div class="fs-2hx fw-bold">{{ (int) ($boardSummary['pending_gain_reviews'] ?? 0) }}</div></div></div></div>
            </div>

            <div class="card card-flush mb-6"><div class="card-body py-4"><form method="GET" action="{{ route('storage-manager.counts.index') }}" class="d-flex align-items-center gap-3 flex-wrap"><label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label><select name="location_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()"><option value="0">@lang('messages.all')</option>@foreach($locations as $id => $name)<option value="{{ $id }}" @selected((int) $locationId === (int) $id)>{{ $name }}</option>@endforeach</select><span class="text-muted fs-7">Positive count gains remain in manual review until the source-truth stock path is explicitly resolved.</span></form></div></div>

            @if(auth()->user()->can('storage_manager.count'))
                <form method="POST" action="{{ route('storage-manager.counts.store') }}">
                    @csrf
                    <div class="card card-flush mb-6"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">Create Count Session</h3><div class="card-toolbar"><button type="submit" class="btn btn-sm btn-primary">Create Session</button></div></div><div class="card-body pt-0"><div class="row g-5"><div class="col-md-4"><label class="form-label fw-semibold">@lang('business.location')</label><select name="location_id" class="form-select form-select-solid">@foreach($locations as $id => $name)<option value="{{ $id }}" @selected((int) old('location_id', $locationId) === (int) $id)>{{ $name }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label fw-semibold">Area</label><select name="area_id" class="form-select form-select-solid"><option value="">All countable areas</option>@foreach($areaOptions as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select></div><div class="col-md-2"><label class="form-label fw-semibold">@lang('lang_v1.freeze_mode')</label><select name="freeze_mode" class="form-select form-select-solid"><option value="soft">@lang('lang_v1.soft_freeze')</option><option value="hard">@lang('lang_v1.hard_freeze')</option></select></div><div class="col-md-2"><label class="form-label fw-semibold">@lang('lang_v1.blind_count')</label><div class="form-check form-check-custom form-check-solid mt-4"><input class="form-check-input" type="checkbox" name="blind_count" value="1"></div></div></div></div></div>
                </form>
            @endif

            <div class="card card-flush mb-6"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">Cycle Count Sessions</h3></div><div class="card-body pt-0"><div class="table-responsive"><table class="table table-row-dashed align-middle"><thead><tr class="fw-bold text-gray-800"><th>Session</th><th>Area</th><th>@lang('lang_v1.freeze_mode')</th><th>@lang('lang_v1.blind_count')</th><th>Lines</th><th>@lang('lang_v1.variance_qty')</th><th>@lang('lang_v1.status')</th><th class="text-end">@lang('messages.action')</th></tr></thead><tbody>@forelse($sessionRows as $row)<tr><td class="fw-semibold text-gray-900">{{ $row['session_no'] ?? '-' }}</td><td>{{ $row['area_name'] ?? 'All enabled areas' }}</td><td>{{ ucfirst($row['freeze_mode'] ?? 'soft') }}</td><td>{{ !empty($row['blind_count']) ? 'Yes' : 'No' }}</td><td>{{ (int) ($row['line_count'] ?? 0) }}</td><td>{{ format_quantity_value($row['variance_qty'] ?? 0) }}</td><td><span class="badge badge-light-primary">{{ $row['status'] ?? '-' }}</span></td><td class="text-end"><a href="{{ route('storage-manager.counts.show', $row['id']) }}" class="btn btn-sm btn-light-primary">@lang('messages.view')</a></td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-8">No cycle count sessions yet.</td></tr>@endforelse</tbody></table></div></div></div>

            <div class="card card-flush"><div class="card-header pt-6"><h3 class="card-title fw-bold text-gray-900">Count Approval Queue</h3></div><div class="card-body pt-0"><div class="table-responsive"><table class="table table-row-dashed align-middle"><thead><tr class="fw-bold text-gray-800"><th>ID</th><th>Session</th><th>Type</th><th>@lang('sale.qty')</th><th>@lang('lang_v1.status')</th></tr></thead><tbody>@forelse($approvalRows as $approval)<tr><td>{{ $approval['id'] ?? '-' }}</td><td>{{ $approval['session_id'] ?? '-' }}</td><td>{{ $approval['approval_type'] ?? '-' }}</td><td>{{ format_quantity_value($approval['threshold_value'] ?? 0) }}</td><td><span class="badge badge-light-warning">{{ $approval['status'] ?? '-' }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-8">No count approvals pending.</td></tr>@endforelse</tbody></table></div></div></div>
        </div>
    </div>
</div>
@endsection
