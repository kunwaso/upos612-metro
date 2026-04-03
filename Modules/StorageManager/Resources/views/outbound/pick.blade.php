@extends('layouts.app')

@section('title', __('lang_v1.pick_workbench'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ $document->document_no ?? __('lang_v1.pick_workbench') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.pick_workbench')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('storage-manager.outbound.index') }}" class="btn btn-sm btn-light">@lang('messages.back')</a>
                <a href="{{ route('storage-manager.outbound.pack.show', $orderSummary['source_id']) }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.pack_workbench')</a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @if(session('status'))
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-6">{{ session('status.msg') }}</div>
            @endif

            <div class="row g-5 g-xl-8 mb-6">
                <div class="col-lg-3">
                    <div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.source')</div><div class="fs-4 fw-bold text-gray-900">{{ $orderSummary['source_ref'] ?? '-' }}</div><div class="text-muted fs-7">{{ $orderSummary['source_status'] ?? '-' }}</div></div></div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('contact.customer')</div><div class="fs-4 fw-bold text-gray-900">{{ $orderSummary['customer_name'] ?? '-' }}</div><div class="text-muted fs-7">{{ $orderSummary['location_name'] ?? '-' }}</div></div></div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('sale.date')</div><div class="fs-4 fw-bold text-gray-900">{{ !empty($orderSummary['transaction_date']) ? \Illuminate\Support\Carbon::parse($orderSummary['transaction_date'])->format('Y-m-d H:i') : '-' }}</div><div class="text-muted fs-7">{{ $orderSummary['execution_mode'] ?? '-' }}</div></div></div>
                </div>
                <div class="col-lg-3">
                    <div class="card card-flush h-100"><div class="card-body"><div class="text-gray-500 fs-7">@lang('lang_v1.shipping_status')</div><div class="fs-4 fw-bold text-gray-900">{{ $orderSummary['shipping_status'] ?? 'ordered' }}</div><div class="text-muted fs-7">{{ $document->status ?? '-' }}</div></div></div>
                </div>
            </div>

            @if(auth()->user()->can('storage_manager.operate') && !empty($orderSummary['can_confirm']))
                <form method="POST" action="{{ route('storage-manager.outbound.pick.confirm', $document->id) }}">
                    @csrf
                    <div class="card card-flush">
                        <div class="card-header pt-6">
                            <h3 class="card-title fw-bold text-gray-900">@lang('lang_v1.pick_queue')</h3>
                            <div class="card-toolbar"><button type="submit" class="btn btn-sm btn-primary">@lang('lang_v1.confirm_pick')</button></div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    <thead>
                                        <tr class="fw-bold text-gray-800">
                                            <th>@lang('product.product')</th>
                                            <th>@lang('product.sku')</th>
                                            <th>@lang('sale.qty')</th>
                                            <th>@lang('lang_v1.source_slot')</th>
                                            <th>@lang('lang_v1.available_qty')</th>
                                            <th>@lang('purchase.lot_number')</th>
                                            <th>@lang('product.exp_date')</th>
                                            <th>@lang('lang_v1.result')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($lineRows as $line)
                                            <tr>
                                                <td class="fw-semibold text-gray-900">{{ $line['product_label'] ?? '-' }}</td>
                                                <td>{{ $line['sku'] ?? '-' }}</td>
                                                <td><input type="number" step="0.0001" min="0" name="lines[{{ $line['id'] }}][executed_qty]" class="form-control form-control-sm form-control-solid" value="{{ old('lines.' . $line['id'] . '.executed_qty', $line['qty'] ?? 0) }}"></td>
                                                <td>
                                                    <select name="lines[{{ $line['id'] }}][source_slot_id]" class="form-select form-select-sm form-select-solid">
                                                        <option value="">Select</option>
                                                        @foreach($sourceSlotOptions as $slotId => $slotLabel)
                                                            <option value="{{ $slotId }}" @selected((string) old('lines.' . $line['id'] . '.source_slot_id', (string) ($line['source_slot_id'] ?? '')) === (string) $slotId)>{{ $slotLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>{{ format_quantity_value($line['available_qty'] ?? 0) }}</td>
                                                <td>{{ $line['lot_number'] ?? '-' }}</td>
                                                <td>{{ !empty($line['expiry_date']) ? \Illuminate\Support\Carbon::parse($line['expiry_date'])->format('Y-m-d') : '-' }}</td>
                                                <td><span class="badge badge-light-{{ (string) ($line['result_status'] ?? '') === 'completed' ? 'success' : 'warning' }}">{{ $line['result_status'] ?? '-' }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="8" class="text-center text-muted py-8">@lang('lang_v1.no_records_found')</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="alert alert-info">@lang('lang_v1.outbound_pick_not_ready')</div>
            @endif
        </div>
    </div>
</div>
@endsection
