@extends('layouts.app')

@section('title', __('lang_v1.purchasing_advisories'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    @lang('lang_v1.purchasing_advisories')
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.purchasing_advisories')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.control-tower.index') }}" class="btn btn-sm btn-light">@lang('lang_v1.control_tower')</a>
                <a href="{{ route('purchase-requisition.index') }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.purchase_requisition')</a>
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

            @if(! $purchaseRequisitionEnabled)
                <div class="alert alert-warning mb-6">
                    @lang('lang_v1.purchase_requisition_feature_disabled')
                </div>
            @endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-4"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.purchasing_review_rows')</div><div class="fs-2hx fw-bold">{{ (int) ($summary['shortage_count'] ?? 0) }}</div></div></div></div>
                <div class="col-md-4"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.external_shortage_qty')</div><div class="fs-2hx fw-bold">{{ format_quantity_value($summary['total_external_shortage_qty'] ?? 0) }}</div></div></div></div>
                <div class="col-md-4"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.open_purchase_requisitions')</div><div class="fs-2hx fw-bold">{{ (int) ($summary['open_requisitions'] ?? 0) }}</div></div></div></div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.planning.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                            <option value="0">@lang('messages.all')</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected((int) $locationId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted fs-7">@lang('lang_v1.purchasing_advisory_help')</span>
                    </form>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.purchasing_advisories')</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="fw-bold text-gray-800">
                                    <th>@lang('business.location')</th>
                                    <th>@lang('product.product')</th>
                                    <th>@lang('lang_v1.source_slot')</th>
                                    <th>@lang('lang_v1.destination_slot')</th>
                                    <th>@lang('lang_v1.source_qty')</th>
                                    <th>@lang('lang_v1.recommended_qty')</th>
                                    <th>@lang('lang_v1.external_shortage_qty')</th>
                                    <th>@lang('lang_v1.purchase_requisition')</th>
                                    <th class="text-end">@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row['location_name'] ?? '-' }}</td>
                                        <td class="fw-semibold text-gray-900">
                                            {{ $row['product_label'] ?? '-' }}
                                            <div class="text-muted fs-8">{{ $row['sku'] ?? '-' }}</div>
                                        </td>
                                        <td>{{ $row['source_label'] ?? '-' }}</td>
                                        <td>{{ $row['destination_label'] ?? '-' }}</td>
                                        <td>{{ format_quantity_value($row['source_qty'] ?? 0) }}</td>
                                        <td>{{ format_quantity_value($row['recommended_qty'] ?? 0) }}</td>
                                        <td>{{ format_quantity_value($row['external_shortage_qty'] ?? 0) }}</td>
                                        <td>
                                            @if(!empty($row['purchase_requisition_id']))
                                                <div class="fw-semibold text-gray-900">{{ $row['purchase_requisition_ref'] ?? ('#' . $row['purchase_requisition_id']) }}</div>
                                                <div class="text-muted fs-8">{{ ucfirst((string) ($row['purchase_requisition_status'] ?? 'ordered')) }}</div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($purchaseRequisitionEnabled && !empty($row['can_create_requisition']) && auth()->user()->can('purchase_requisition.create') && (auth()->user()->can('storage_manager.manage') || auth()->user()->can('storage_manager.approve')))
                                                <form method="POST" action="{{ route('storage-manager.planning.store', $row['rule_id']) }}" class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                                                    @csrf
                                                    <input type="number" name="quantity" value="{{ number_format((float) ($row['external_shortage_qty'] ?? 0), 4, '.', '') }}" step="0.0001" min="0.0001" class="form-control form-control-sm w-100px" />
                                                    <input type="date" name="delivery_date" class="form-control form-control-sm w-150px" />
                                                    <input type="text" name="notes" class="form-control form-control-sm w-200px" placeholder="@lang('lang_v1.notes_optional')" />
                                                    <button type="submit" class="btn btn-sm btn-light-primary">@lang('lang_v1.create_purchase_requisition')</button>
                                                </form>
                                            @elseif(!empty($row['purchase_requisition_id']))
                                                <a href="{{ route('purchase-requisition.index') }}" class="btn btn-sm btn-light">@lang('messages.view')</a>
                                            @else
                                                <span class="text-muted fs-8">@lang('lang_v1.requisition_already_open')</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-8">@lang('lang_v1.no_purchasing_advisories')</td>
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
