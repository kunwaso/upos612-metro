@extends('layouts.app')

@section('title', __('lang_v1.warehouse_settings'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <x-storagemanager::storage-toolbar
        :title="$storageToolbarTitle"
        :breadcrumbs="$storageToolbarBreadcrumbs"
        :map-location-id="$storageToolbarLocationId ?? null"
    />

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @if(session('status'))
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-6">
                    {{ session('status.msg') }}
                </div>
            @endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-3">
                    <div class="card card-flush h-100"><div class="card-body"><div class="fw-semibold text-gray-500 fs-7">@lang('business.location')</div><div class="fs-2hx fw-bold text-gray-900">{{ $metrics['location_count'] }}</div></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush h-100"><div class="card-body"><div class="fw-semibold text-gray-500 fs-7">@lang('lang_v1.execution_mode')</div><div class="fs-2hx fw-bold text-gray-900">{{ $metrics['strict_locations'] }}</div><div class="text-gray-500 fs-7 mt-1">Strict</div></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush h-100"><div class="card-body"><div class="fw-semibold text-gray-500 fs-7">@lang('lang_v1.scanner_mode')</div><div class="fs-2hx fw-bold text-gray-900">{{ $metrics['scanner_ready_locations'] }}</div><div class="text-gray-500 fs-7 mt-1">Desktop ready</div></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush h-100"><div class="card-body"><div class="fw-semibold text-gray-500 fs-7">@lang('lang_v1.vas_sync')</div><div class="fs-2hx fw-bold text-gray-900">{{ $metrics['vas_sync_locations'] }}</div><div class="text-gray-500 fs-7 mt-1">Enforced</div></div></div>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.per_location_execution_settings')</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-gray-800 border-bottom border-gray-200">
                                    <th>@lang('business.location')</th>
                                    <th>@lang('lang_v1.execution_mode')</th>
                                    <th>@lang('lang_v1.scanner_mode')</th>
                                    <th>@lang('lang_v1.bypass_policy')</th>
                                    <th>@lang('lang_v1.tracking_controls')</th>
                                    <th>@lang('lang_v1.default_execution_areas')</th>
                                    <th>@lang('lang_v1.vas_sync')</th>
                                    <th class="text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($viewRows as $row)
                                    @php $areaOptions = $areasByLocation->get($row['location_id'], collect()); @endphp
                                    <tr>
                                        <td class="align-top">
                                            <div class="fw-semibold text-gray-900">{{ $row['location_name'] }}</div>
                                            <div class="text-gray-500 fs-7 mt-1">
                                                @if($row['lot_ready'])
                                                    <span class="badge badge-light-success">@lang('lang_v1.lot_expiry_ready')</span>
                                                @else
                                                    <span class="badge badge-light-warning">
                                                        @lang('lang_v1.lot_expiry_attention')
                                                        ({{ $row['lot_missing_count'] + $row['expiry_missing_count'] }})
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-gray-500 fs-8 mt-2">
                                                Lots missing: {{ $row['lot_missing_count'] }}<br>
                                                Expiry missing: {{ $row['expiry_missing_count'] }}
                                            </div>
                                        </td>
                                        <td colspan="6">
                                            <form method="POST" action="{{ route('storage-manager.settings.update', $row['location_id']) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="row g-4">
                                                    <div class="col-xl-2 col-lg-4">
                                                        <label class="form-label fs-7 text-gray-600">@lang('lang_v1.execution_mode')</label>
                                                        <select class="form-select form-select-solid form-select-sm" name="execution_mode">
                                                            @foreach($executionModes as $value => $label)
                                                                <option value="{{ $value }}" @selected($row['execution_mode'] === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-4">
                                                        <label class="form-label fs-7 text-gray-600">@lang('lang_v1.scanner_mode')</label>
                                                        <select class="form-select form-select-solid form-select-sm" name="scanner_mode">
                                                            @foreach($scannerModes as $value => $label)
                                                                <option value="{{ $value }}" @selected($row['scanner_mode'] === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-4">
                                                        <label class="form-label fs-7 text-gray-600">@lang('lang_v1.bypass_policy')</label>
                                                        <select class="form-select form-select-solid form-select-sm" name="bypass_policy">
                                                            @foreach($bypassPolicies as $value => $label)
                                                                <option value="{{ $value }}" @selected($row['bypass_policy'] === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-xl-2 col-lg-4">
                                                        <label class="form-label fs-7 text-gray-600">@lang('lang_v1.status')</label>
                                                        <select class="form-select form-select-solid form-select-sm" name="status">
                                                            <option value="active" @selected($row['status'] === 'active')>@lang('lang_v1.active')</option>
                                                            <option value="inactive" @selected($row['status'] === 'inactive')>@lang('lang_v1.inactive')</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-xl-4">
                                                        <label class="form-label fs-7 text-gray-600">@lang('lang_v1.tracking_controls')</label>
                                                        <div class="d-flex flex-wrap gap-6 pt-2">
                                                            <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                                <input class="form-check-input" type="checkbox" name="require_lot_tracking" value="1" @checked($row['require_lot_tracking'])>
                                                                <span class="form-check-label">@lang('lang_v1.lot_tracking')</span>
                                                            </label>
                                                            <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                                <input class="form-check-input" type="checkbox" name="require_expiry_tracking" value="1" @checked($row['require_expiry_tracking'])>
                                                                <span class="form-check-label">@lang('lang_v1.expiry_tracking')</span>
                                                            </label>
                                                            <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                                <input class="form-check-input" type="checkbox" name="enforce_vas_sync" value="1" @checked($row['enforce_vas_sync'])>
                                                                <span class="form-check-label">@lang('lang_v1.enforce_vas_sync')</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="row g-4">
                                                            @foreach([
                                                                'default_receiving_area_id' => __('lang_v1.default_receiving_area'),
                                                                'default_staging_area_id' => __('lang_v1.default_staging_area'),
                                                                'default_packing_area_id' => __('lang_v1.default_packing_area'),
                                                                'default_dispatch_area_id' => __('lang_v1.default_dispatch_area'),
                                                                'default_quarantine_area_id' => __('lang_v1.default_quarantine_area'),
                                                                'default_damaged_area_id' => __('lang_v1.default_damaged_area'),
                                                                'default_count_hold_area_id' => __('lang_v1.default_count_hold_area'),
                                                            ] as $field => $label)
                                                                <div class="col-xl-3 col-lg-4 col-md-6">
                                                                    <label class="form-label fs-7 text-gray-600">{{ $label }}</label>
                                                                    <select class="form-select form-select-solid form-select-sm" name="{{ $field }}">
                                                                        <option value="">@lang('lang_v1.none')</option>
                                                                        @foreach($areaOptions as $option)
                                                                            <option value="{{ $option['id'] }}" @selected((int) $row[$field] === (int) $option['id'])>{{ $option['label'] }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-4">
                                                    <div class="text-gray-500 fs-8">
                                                        @lang('lang_v1.last_updated'): {{ $row['updated_at'] ?: '—' }}
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-primary">@lang('messages.update')</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="text-end align-top">
                                            <div class="badge {{ $row['enforce_vas_sync'] ? 'badge-light-primary' : 'badge-light-secondary' }}">
                                                {{ $row['enforce_vas_sync'] ? __('lang_v1.enforced') : __('lang_v1.optional') }}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-10">@lang('lang_v1.no_active_locations_found')</td>
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
