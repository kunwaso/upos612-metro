@extends('layouts.app')

@section('title', __('lang_v1.outbound_execution'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    @lang('lang_v1.outbound_execution')
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.outbound_execution')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.transfers.index') }}" class="btn btn-sm btn-light">@lang('lang_v1.transfer_execution')</a>
                <a href="{{ route('storage-manager.control-tower.index') }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.control_tower')</a>
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

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-md-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.enabled_locations')</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($summary['enabled_location_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.pick_queue')</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($summary['pick_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.pack_queue')</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($summary['pack_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush h-100">
                        <div class="card-body">
                            <div class="text-gray-500 fs-7">@lang('lang_v1.ship_queue')</div>
                            <div class="fs-2hx fw-bold">{{ (int) ($summary['ship_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.outbound.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="fw-semibold text-gray-700 fs-6">@lang('business.location')</label>
                        <select name="location_id" class="form-select form-select-solid w-250px" onchange="this.form.submit()">
                            <option value="0">@lang('messages.all')</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}" @selected((int) $locationId === (int) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted fs-7">@lang('lang_v1.sales_order_only_execution_note')</span>
                    </form>
                </div>
            </div>

            @php
                $tables = [
                    ['title' => __('lang_v1.pick_queue'), 'rows' => $pickRows, 'route' => 'storage-manager.outbound.pick.show'],
                    ['title' => __('lang_v1.pack_queue'), 'rows' => $packRows, 'route' => 'storage-manager.outbound.pack.show'],
                    ['title' => __('lang_v1.ship_queue'), 'rows' => $shipRows, 'route' => 'storage-manager.outbound.ship.show'],
                ];
            @endphp

            @foreach($tables as $table)
                <div class="card card-flush mb-6">
                    <div class="card-header pt-6">
                        <h3 class="card-title fw-bold text-gray-900">{{ $table['title'] }}</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead>
                                    <tr class="fw-bold text-gray-800">
                                        <th>@lang('lang_v1.source')</th>
                                        <th>@lang('contact.customer')</th>
                                        <th>@lang('business.location')</th>
                                        <th>@lang('sale.date')</th>
                                        <th>@lang('sale.qty')</th>
                                        <th>@lang('lang_v1.execution_mode')</th>
                                        <th>@lang('lang_v1.shipping_status')</th>
                                        <th>Document</th>
                                        <th class="text-end">@lang('messages.action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($table['rows'] as $row)
                                        <tr>
                                            <td class="fw-semibold text-gray-900">
                                                <div>{{ $row['source_ref'] ?? '-' }}</div>
                                                <div class="text-muted fs-8">{{ $row['source_status'] ?? '-' }}</div>
                                            </td>
                                            <td>{{ $row['customer_name'] ?? '-' }}</td>
                                            <td>{{ $row['location_name'] ?? '-' }}</td>
                                            <td>{{ !empty($row['transaction_date']) ? \Illuminate\Support\Carbon::parse($row['transaction_date'])->format('Y-m-d H:i') : '-' }}</td>
                                            <td>{{ format_quantity_value($row['expected_qty'] ?? 0) }} <span class="text-muted fs-8">({{ (int) ($row['line_count'] ?? 0) }} lines)</span></td>
                                            <td>{{ ucwords(str_replace('_', ' ', (string) ($row['execution_mode'] ?? 'off'))) }}</td>
                                            <td>
                                                <span class="badge badge-light-info">{{ $row['shipping_status'] ?? 'ordered' }}</span>
                                            </td>
                                            <td>
                                                @if(!empty($row['document_id']))
                                                    <div class="fw-semibold text-gray-900">{{ $row['document_no'] ?? '-' }}</div>
                                                    <div class="text-muted fs-8">{{ $row['document_status'] ?? 'not_required' }}</div>
                                                @else
                                                    <span class="text-muted fs-8">@lang('lang_v1.not_generated')</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if(!empty($row['source_id']))
                                                    <a href="{{ route($table['route'], $row['source_id']) }}" class="btn btn-sm btn-light-primary">@lang('messages.view')</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-8">@lang('lang_v1.no_records_found')</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
