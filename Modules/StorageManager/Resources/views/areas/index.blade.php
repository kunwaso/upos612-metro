@extends('layouts.app')

@section('title', __('lang_v1.warehouse_areas'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    @lang('lang_v1.warehouse_areas')
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.warehouse_areas')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.areas.create') }}" class="btn btn-sm btn-primary">@lang('lang_v1.add_warehouse_area')</a>
                <a href="{{ route('storage-manager.settings.index') }}" class="btn btn-sm btn-light">@lang('lang_v1.warehouse_settings')</a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="fw-semibold text-gray-500 fs-7">@lang('lang_v1.warehouse_areas')</div><div class="fs-2hx fw-bold text-gray-900">{{ $metrics['area_count'] }}</div></div></div></div>
                <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="fw-semibold text-gray-500 fs-7">@lang('lang_v1.receiving')</div><div class="fs-2hx fw-bold text-gray-900">{{ $metrics['receiving_count'] }}</div></div></div></div>
                <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="fw-semibold text-gray-500 fs-7">@lang('lang_v1.quarantine')</div><div class="fs-2hx fw-bold text-gray-900">{{ $metrics['quarantine_count'] }}</div></div></div></div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.areas.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-sm form-select-solid w-250px" onchange="this.form.submit()">
                            <option value="0">@lang('messages.all')</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected($locationId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-gray-800 border-bottom border-gray-200">
                                    <th>@lang('lang_v1.code')</th>
                                    <th>@lang('messages.name')</th>
                                    <th>@lang('business.location')</th>
                                    <th>@lang('lang_v1.area_type')</th>
                                    <th>@lang('lang_v1.zone')</th>
                                    <th>@lang('lang_v1.storage_slots')</th>
                                    <th>@lang('lang_v1.max_capacity')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th class="text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td><span class="badge badge-light-primary">{{ $row['code'] }}</span></td>
                                        <td class="fw-semibold text-gray-900">{{ $row['name'] }}</td>
                                        <td>{{ $row['location_name'] }}</td>
                                        <td>{{ $row['area_type'] }}</td>
                                        <td>{{ $row['category_name'] ?: '—' }}</td>
                                        <td>{{ $row['slot_count'] }}</td>
                                        <td>{{ $row['capacity_sum'] ?: '∞' }}</td>
                                        <td><span class="badge {{ $row['status'] === 'active' ? 'badge-light-success' : 'badge-light-secondary' }}">{{ $row['status'] }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('storage-manager.areas.edit', $row['id']) }}" class="btn btn-sm btn-light-primary">@lang('messages.edit')</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-10">@lang('lang_v1.no_warehouse_areas_found')</td>
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
