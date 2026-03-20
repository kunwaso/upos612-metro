@extends('layouts.app')

@section('title', __('lang_v1.running_out_of_stock'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    @lang('lang_v1.running_out_of_stock')
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('storage-manager.index') }}" class="text-muted text-hover-primary">@lang('lang_v1.storage_manager')</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.running_out_of_stock')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.index', ['location_id' => $location_id]) }}" class="btn btn-sm btn-light">
                    <i class="ki-duotone ki-arrow-left fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                    @lang('lang_v1.warehouse_map')
                </a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.running-out') }}" class="d-flex align-items-center flex-wrap gap-3">
                        <label class="fw-semibold text-gray-700 fs-6 text-nowrap">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-sm form-select-solid w-250px" onchange="this.form.submit()">
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected($location_id == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @if($product_id)
                            <input type="hidden" name="product_id" value="{{ $product_id }}">
                            <span class="badge badge-light-info">@lang('lang_v1.filtered_product')</span>
                            <a href="{{ route('storage-manager.running-out', ['location_id' => $location_id]) }}" class="btn btn-sm btn-light-danger">
                                @lang('lang_v1.clear_filter')
                            </a>
                        @endif
                        <span class="ms-auto text-gray-500 fw-semibold fs-7">
                            {{ $selectedLocation->name ?? '—' }}
                        </span>
                    </form>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.running_out_of_stock')</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-gray-800 border-bottom border-gray-200">
                                    <th class="min-w-250px">@lang('product.product')</th>
                                    <th class="min-w-160px">@lang('business.location')</th>
                                    <th class="min-w-140px">@lang('lang_v1.stock')</th>
                                    <th class="min-w-120px">@lang('lang_v1.storage_slot')</th>
                                    <th class="min-w-120px">@lang('lang_v1.status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $item['product_label'] }}</td>
                                        <td class="text-gray-700">{{ $selectedLocation->name ?? '—' }}</td>
                                        <td class="text-gray-700">{{ $item['meta_line'] }}</td>
                                        <td>
                                            <span class="badge badge-light-primary">{{ $item['storage_label'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-danger">@lang('lang_v1.running_out')</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-8">@lang('lang_v1.no_running_out_items')</td>
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
