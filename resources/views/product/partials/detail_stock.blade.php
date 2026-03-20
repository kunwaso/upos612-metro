{{-- Product detail: Stock tab – rack/location details + per-location stock (AJAX) --}}
<div class="col-12">
    <div class="card card-flush">
        <div class="card-header">
            <h3 class="card-title fw-bold text-gray-900">@lang('product.stock')</h3>
        </div>
        <div class="card-body pt-0">
            @if(!empty($details) && count($details) > 0 && (($enableRacks ?? false) || ($enableRow ?? false) || ($enablePosition ?? false)))
            <div class="mb-7">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="fw-semibold fs-6 text-gray-700">@lang('lang_v1.rack_details')</div>
                    @can('storage_manager.view')
                    <a href="{{ route('storage-manager.index') }}" class="btn btn-sm btn-light-primary">
                        <i class="ki-duotone ki-map fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                        @lang('lang_v1.warehouse_map')
                    </a>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-gray-800 border-bottom border-gray-200">
                                <th class="min-w-150px">@lang('business.location')</th>
                                @if($enableRacks ?? false)
                                    <th class="min-w-120px">@lang('lang_v1.rack')</th>
                                @endif
                                @if($enableRow ?? false)
                                    <th class="min-w-100px">@lang('lang_v1.row')</th>
                                @endif
                                @if($enablePosition ?? false)
                                    <th class="min-w-100px">@lang('lang_v1.position')</th>
                                @endif
                                <th class="min-w-120px">@lang('lang_v1.storage_slot_code')</th>
                                                @can('storage_manager.manage')
                                                <th class="min-w-80px"></th>
                                                @endcan
                                            </tr>
                                        </thead>
                        <tbody>
                            @foreach($details as $detail)
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-gray-800">{{ $detail->name ?? '—' }}</span>
                                        @if(!empty($detail->category_name))
                                            <div class="text-muted fs-8 mt-1">
                                                <i class="ki-duotone ki-category fs-7 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                                {{ $detail->category_name }}
                                            </div>
                                        @endif
                                    </td>
                                    @if($enableRacks ?? false)
                                        <td class="text-gray-700">{{ $detail->rack ?? '—' }}</td>
                                    @endif
                                    @if($enableRow ?? false)
                                        <td class="text-gray-700">{{ $detail->row ?? '—' }}</td>
                                    @endif
                                    @if($enablePosition ?? false)
                                        <td class="text-gray-700">{{ $detail->position ?? '—' }}</td>
                                    @endif
                                    <td>
                                        @php
                                            $parts = array_filter([
                                                $detail->rack ?? null,
                                                $detail->row ?? null,
                                                $detail->position ?? null,
                                            ]);
                                            $slotLabel = $detail->slot_code ?? (count($parts) ? implode('-', $parts) : null);
                                        @endphp
                                        @if($slotLabel)
                                            <span class="badge badge-light-primary fw-semibold fs-7">{{ $slotLabel }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    @can('storage_manager.manage')
                                    <td>
                                        <button type="button"
                                            class="btn btn-sm btn-light btn-change-slot"
                                            data-product-id="{{ $product->id }}"
                                            data-location-id="{{ $detail->location_id ?? '' }}"
                                            data-current-slot="{{ $detail->slot_id ?? '' }}">
                                            <i class="ki-duotone ki-arrows-circle fs-5"><span class="path1"></span><span class="path2"></span></i>
                                            @lang('lang_v1.change_slot')
                                        </button>
                                    </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            @if($product->enable_stock == 1)
            <div class="fw-semibold fs-6 text-gray-700 mb-3">@lang('lang_v1.product_stock_details')</div>
            <div id="view_product_stock_details" data-product_id="{{ $product->id }}"></div>
            @endif
        </div>
    </div>
</div>

@can('storage_manager.manage')
{{-- Change Slot Modal — script wired in detail.blade.php @section('javascript') --}}
<div class="modal fade" id="changeSlotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-400px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-gray-900">@lang('lang_v1.change_slot')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="changeSlotLoading" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm text-primary"></span>
                </div>
                <div id="changeSlotContent" class="d-none">
                    <label class="form-label fw-semibold fs-6">@lang('lang_v1.assign_slot')</label>
                    <select id="changeSlotSelect" class="form-select form-select-solid">
                        <option value="">— @lang('messages.select') —</option>
                    </select>
                </div>
                <div id="changeSlotEmpty" class="d-none text-center text-muted py-4 fs-6">
                    @lang('lang_v1.no_slots_defined')
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" id="changeSlotSave" class="btn btn-primary" disabled>@lang('lang_v1.assign_slot')</button>
            </div>
        </div>
    </div>
</div>
@endcan
