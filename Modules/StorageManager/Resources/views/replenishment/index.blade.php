@extends('layouts.app')

@section('title', __('lang_v1.replenishment_queue'))

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
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">Triggered Rules</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($queueSummary['rule_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.open_documents')</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($queueSummary['open_documents'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.recommended_qty')</div>
                            <div class="fs-2hx fw-bold">{{ format_quantity_value($queueSummary['recommended_qty'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.replenishment.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                            <option value="0">@lang('messages.all')</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected((int) $locationId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted fs-7">Reserve to forward-pick replenishment queue</span>
                    </form>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.replenishment_queue')</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="fw-bold text-gray-800">
                                    <th>@lang('product.product')</th>
                                    <th>@lang('product.sku')</th>
                                    <th>@lang('lang_v1.source')</th>
                                    <th>@lang('lang_v1.destination')</th>
                                    <th>Destination Qty</th>
                                    <th>Source Qty</th>
                                    <th>@lang('lang_v1.recommended_qty')</th>
                                    <th class="text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row['product_label'] ?? '—' }}</td>
                                        <td>{{ $row['sku'] ?? '—' }}</td>
                                        <td>{{ $row['source_label'] ?? '—' }}</td>
                                        <td>{{ $row['destination_label'] ?? '—' }}</td>
                                        <td>{{ format_quantity_value($row['destination_qty'] ?? 0) }}</td>
                                        <td>{{ format_quantity_value($row['source_qty'] ?? 0) }}</td>
                                        <td>{{ format_quantity_value($row['recommended_qty'] ?? 0) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('storage-manager.replenishment.show', $row['rule_id']) }}" class="btn btn-sm btn-light-primary">@lang('messages.view')</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-8">No replenishment tasks currently triggered.</td>
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
