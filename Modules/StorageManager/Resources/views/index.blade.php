@extends('layouts.app')

@section('title', __('lang_v1.warehouse_map'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    {{-- Toolbar --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    @lang('lang_v1.warehouse_map')
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.warehouse_map')</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @can('storage_manager.manage')
                <a href="{{ route('storage-manager.slots.create') }}" class="btn btn-sm btn-primary">
                    <i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                    @lang('lang_v1.add_storage_slot')
                </a>
                @endcan
                <a href="{{ route('storage-manager.slots.index') }}" class="btn btn-sm btn-light">
                    <i class="ki-duotone ki-element-11 fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                    @lang('lang_v1.storage_slots')
                </a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- Location selector + legend --}}
            <div class="card card-flush mb-6">
                <div class="card-body py-4">
                    <form method="GET" action="{{ route('storage-manager.index') }}" class="d-flex align-items-center flex-wrap gap-4">
                        <div class="d-flex align-items-center gap-3">
                            <label class="fw-semibold text-gray-700 fs-6 text-nowrap">
                                <i class="ki-duotone ki-geolocation fs-4 me-1 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                @lang('business.location')
                            </label>
                            <select name="location_id" id="location_selector" class="form-select form-select-sm form-select-solid w-250px" onchange="this.form.submit()">
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" @selected($location_id == $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Legend --}}
                        <div class="d-flex align-items-center gap-4 ms-auto">
                            <div class="d-flex align-items-center gap-2">
                                <span class="w-15px h-15px rounded-2 bg-light-primary border border-primary"></span>
                                <span class="fw-semibold fs-7 text-gray-600">@lang('lang_v1.slot_available')</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="w-15px h-15px rounded-2 bg-light-danger border border-danger"></span>
                                <span class="fw-semibold fs-7 text-gray-600">@lang('lang_v1.slot_full')</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($selectedLocation)
                <div class="fw-semibold fs-5 text-gray-700 mb-5">
                    <i class="ki-duotone ki-map fs-3 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                    {{ $selectedLocation->name }}
                </div>
            @endif

            <div class="row g-6">
                <div class="col-12 col-xxl-8">
                    @if(empty($zones))
                        <div class="card card-flush">
                            <div class="card-body text-center py-10">
                                <i class="ki-duotone ki-element-11 fs-3x text-gray-400 mb-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                <p class="text-muted fw-semibold fs-6">@lang('lang_v1.no_slots_defined')</p>
                                @can('storage_manager.manage')
                                <a href="{{ route('storage-manager.slots.create') }}" class="btn btn-sm btn-primary mt-2">
                                    @lang('lang_v1.add_storage_slot')
                                </a>
                                @endcan
                            </div>
                        </div>
                    @else
                        {{-- Grid: 3 zone cards per row like the mockup --}}
                        <div class="row g-6 g-xl-9 mb-0">
                            @foreach($zones as $zone)
                                @php
                                    $slots        = $zone['slots'];
                                    $maxVisible   = 9;
                                    $visibleSlots = $slots->take($maxVisible);
                                    $slotsByRow   = $visibleSlots
                                        ->sortBy(function ($slot) {
                                            $row = is_numeric($slot->row ?? null) ? (int) $slot->row : 0;
                                            $position = is_numeric($slot->position ?? null) ? (int) $slot->position : 0;

                                            return sprintf('%05d-%05d', $row, $position);
                                        })
                                        ->groupBy(function ($slot) {
                                            return is_numeric($slot->row ?? null) ? (int) $slot->row : 0;
                                        });
                                    $overflow     = $slots->count() - $maxVisible;
                                    $hasOverflow  = $overflow > 0;
                                    $totalOccupied  = $zone['occupied'];
                                    $totalCapacity  = $zone['capacity'];
                                @endphp
                                <div class="col-md-6 col-xl-4">
                                    <div class="card card-flush h-100">
                                        {{-- Decorative hatched header strip (matches mockup) --}}
                                        <div class="card-header pt-4 pb-0 border-0">
                                            <div class="w-100 rounded-2 mb-3" style="height:10px;background:repeating-linear-gradient(-45deg,#e0e0e0,#e0e0e0 4px,#f5f5f5 4px,#f5f5f5 10px);"></div>
                                        </div>
                                        <div class="card-body pt-2 pb-4">
                                            <h4 class="fw-bold text-gray-800 fs-5 mb-5">{{ optional($zone['category'])->name ?? '—' }}</h4>

                                            {{-- Slot cells --}}
                                            <div class="d-flex flex-column gap-3 mb-4">
                                                @foreach($slotsByRow as $rowSlots)
                                                    <div class="d-flex flex-wrap gap-3">
                                                        @foreach($rowSlots as $slot)
                                                            @php
                                                                $isFull  = $slot->is_full ?? false;
                                                                $label   = $slot->slot_code ?: ($slot->row . $slot->position);
                                                                $btnClass = $isFull ? 'btn-light-danger' : 'btn-light-primary';
                                                            @endphp
                                                            <button type="button"
                                                                class="btn btn-sm {{ $btnClass }} fw-semibold rounded-2 px-4 py-2 slot-cell"
                                                                data-slot-id="{{ $slot->id }}"
                                                                data-slot-label="{{ $label }}"
                                                                data-is-full="{{ $isFull ? '1' : '0' }}"
                                                                data-capacity="{{ $slot->max_capacity }}"
                                                                data-occupancy="{{ $slot->occupancy }}"
                                                                data-zone="{{ optional($zone['category'])->name }}"
                                                                data-slot-context="{{ optional($zone['category'])->name }} › @lang('lang_v1.row') {{ $slot->row }} › @lang('lang_v1.position') {{ $slot->position }}">
                                                                {{ $label }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                                @if($hasOverflow)
                                                    <button type="button"
                                                        class="btn btn-sm btn-primary fw-bold rounded-2 px-4 py-2"
                                                        onclick="window.location='{{ route('storage-manager.slots.index', ['location_id' => $location_id]) }}&category_id={{ optional($zone['category'])->id }}'">
                                                        +{{ $overflow }}
                                                    </button>
                                                @endif
                                            </div>

                                            {{-- Decorative hatched footer strip --}}
                                            <div class="w-100 rounded-2 mb-3" style="height:10px;background:repeating-linear-gradient(-45deg,#e0e0e0,#e0e0e0 4px,#f5f5f5 4px,#f5f5f5 10px);"></div>

                                            {{-- Capacity footer --}}
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="text-muted fs-7">
                                                    @lang('lang_v1.occupied'):
                                                    <strong class="text-gray-800">{{ $totalOccupied }}</strong>
                                                    @if($totalCapacity > 0)
                                                        <span class="text-muted"> / {{ $totalCapacity }}</span>
                                                    @endif
                                                </span>
                                                @if($totalCapacity > 0)
                                                    @php $pct = min(100, round($totalOccupied / $totalCapacity * 100)); @endphp
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress h-6px w-80px">
                                                            <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 60 ? 'bg-warning' : 'bg-success') }}"
                                                                 style="width:{{ $pct }}%"></div>
                                                        </div>
                                                        <span class="text-muted fs-8">{{ $pct }}%</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-12 col-xxl-4">
                    {{-- Running out of stock widget --}}
                    <div class="card card-flush mb-6">
                        <div class="card-header pt-7">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-900">@lang('lang_v1.running_out_of_stock')</span>
                            </h3>
                            <div class="card-toolbar">
                                <a href="{{ $widget_meta['running_out_url'] }}" class="btn btn-sm btn-icon btn-light" title="@lang('messages.view')">
                                    <i class="ki-duotone ki-arrow-up-right fs-4"><span class="path1"></span><span class="path2"></span></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="d-flex flex-stack pb-2 mb-3 border-bottom border-gray-200">
                                <span class="text-gray-500 fw-semibold fs-7">@lang('product.product')</span>
                                <span class="text-gray-500 fw-semibold fs-7">@lang('lang_v1.storage_slot')</span>
                            </div>

                            @forelse($running_out_items as $item)
                                <div class="d-flex flex-stack py-3">
                                    <a href="{{ $item['link_url'] ?? $widget_meta['running_out_url'] }}" class="d-flex align-items-center text-decoration-none text-gray-900 text-hover-primary flex-grow-1 me-4">
                                        <div class="symbol symbol-35px me-4">
                                            <span class="symbol-label bg-light-danger">
                                                <i class="ki-duotone ki-package fs-5 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column min-w-0">
                                            <span class="fw-bold fs-6 text-truncate">{{ $item['product_label'] }}</span>
                                            <span class="text-gray-500 fw-semibold fs-7">{{ $item['meta_line'] }}</span>
                                        </div>
                                    </a>
                                    <a href="{{ $item['link_url'] ?? $widget_meta['running_out_url'] }}" class="text-gray-800 fw-bold fs-7 text-hover-primary text-nowrap">
                                        {{ $item['storage_label'] }}
                                    </a>
                                </div>
                                @if(! $loop->last)
                                    <div class="separator separator-dashed"></div>
                                @endif
                            @empty
                                <div class="text-center py-10">
                                    <span class="text-gray-500 fw-semibold fs-7">@lang('lang_v1.no_running_out_items')</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Expiring products widget --}}
                    <div class="card card-flush">
                        <div class="card-header pt-7">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-900">@lang('lang_v1.expiring_products')</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">
                                    @lang('lang_v1.expiry_window_days', ['days' => $widget_meta['expiry_window_days']])
                                </span>
                            </h3>
                            <div class="card-toolbar">
                                <a href="{{ $widget_meta['expiring_url'] }}" class="btn btn-sm btn-icon btn-light" title="@lang('messages.view')">
                                    <i class="ki-duotone ki-arrow-up-right fs-4"><span class="path1"></span><span class="path2"></span></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="d-flex flex-stack pb-2 mb-3 border-bottom border-gray-200">
                                <span class="text-gray-500 fw-semibold fs-7">@lang('product.product')</span>
                                <span class="text-gray-500 fw-semibold fs-7">@lang('lang_v1.storage_slot')</span>
                            </div>

                            @forelse($expiring_items as $item)
                                @php
                                    $statusClass = $item['status'] === 'expired' ? 'badge-light-danger' : 'badge-light-warning';
                                    $statusLabel = $item['status'] === 'expired' ? __('report.expired') : __('lang_v1.expiring');
                                @endphp
                                <div class="d-flex flex-stack py-3">
                                    <a href="{{ $item['link_url'] ?? $widget_meta['expiring_url'] }}" class="d-flex align-items-center text-decoration-none text-gray-900 text-hover-primary flex-grow-1 me-4">
                                        <div class="symbol symbol-35px me-4">
                                            <span class="symbol-label bg-light-warning">
                                                <i class="ki-duotone ki-information-5 fs-5 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column min-w-0">
                                            <span class="fw-bold fs-6 text-truncate">{{ $item['product_label'] }}</span>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-gray-500 fw-semibold fs-7">{{ $item['meta_line'] }}</span>
                                                <span class="badge {{ $statusClass }} fs-8">{{ $statusLabel }}</span>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="{{ $item['link_url'] ?? $widget_meta['expiring_url'] }}" class="text-gray-800 fw-bold fs-7 text-hover-primary text-nowrap">
                                        {{ $item['storage_label'] }}
                                    </a>
                                </div>
                                @if(! $loop->last)
                                    <div class="separator separator-dashed"></div>
                                @endif
                            @empty
                                <div class="text-center py-10">
                                    <span class="text-gray-500 fw-semibold fs-7">@lang('lang_v1.no_expiring_items')</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Slot detail modal --}}
<div class="modal fade" id="slotDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-450px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-gray-900" id="slotModalTitle">—</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row gy-3">
                    <div class="col-6">
                        <div class="fw-semibold text-muted fs-7">@lang('lang_v1.zone')</div>
                        <div class="fw-bold text-gray-800 fs-6" id="slotModalZone">—</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-semibold text-muted fs-7">@lang('lang_v1.storage_slot_code')</div>
                        <div class="fw-bold text-gray-800 fs-6" id="slotModalCode">—</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-semibold text-muted fs-7">@lang('lang_v1.occupied')</div>
                        <div class="fw-bold text-gray-800 fs-6" id="slotModalOccupied">—</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-semibold text-muted fs-7">@lang('lang_v1.max_capacity')</div>
                        <div class="fw-bold text-gray-800 fs-6" id="slotModalCapacity">—</div>
                    </div>
                    <div class="col-12">
                        <div class="fw-semibold text-muted fs-7 mb-1">@lang('lang_v1.available')</div>
                        <span id="slotModalStatus"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                @can('storage_manager.manage')
                <a href="#" id="slotModalEditBtn" class="btn btn-sm btn-primary">
                    <i class="ki-duotone ki-pencil fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                    @lang('messages.edit')
                </a>
                @endcan
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    var slotProductsEndpoint = '{{ route("storage-manager.available-slots") }}';
    var slotProductsCache = {};

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function disposeSlotPopover(buttonEl) {
        var instance = bootstrap.Popover.getInstance(buttonEl);
        if (instance) {
            instance.dispose();
        }
    }

    function hideAllSlotPopovers() {
        $('.slot-cell').each(function () {
            disposeSlotPopover(this);
        });
    }

    function showSlotPopover($button, contentHtml) {
        hideAllSlotPopovers();

        var popover = new bootstrap.Popover($button[0], {
            trigger: 'manual',
            html: true,
            sanitize: false,
            container: 'body',
            placement: 'top',
            content: contentHtml
        });

        popover.show();
    }

    function buildSlotProductsContent(payload, slotLabel, slotContext) {
        var items = (payload && payload.slot_products) ? payload.slot_products : [];
        var total = payload && payload.slot_products_total ? payload.slot_products_total : 0;
        var html = '';

        html += '<div class="fw-bold text-gray-900 fs-7 mb-1">' + escapeHtml(slotLabel) + '</div>';
        if (slotContext) {
            html += '<div class="text-muted fs-8 mb-2">' + escapeHtml(slotContext) + '</div>';
        }

        if (!items.length) {
            html += '<div class="text-muted fw-semibold fs-7">No products assigned</div>';
            return html;
        }

        html += '<div class="d-flex flex-column gap-2">';
        $.each(items, function (_, item) {
            html += '<div class="d-flex justify-content-between align-items-start gap-2">';
            html += '<span class="fw-semibold text-gray-800 fs-7">' + escapeHtml(item.product_label) + '</span>';
            html += '<span class="badge badge-light-primary fs-8">x' + escapeHtml(item.assignments) + '</span>';
            html += '</div>';
        });
        html += '</div>';

        if (total > items.length) {
            html += '<div class="text-muted fs-8 mt-2">+' + escapeHtml(total - items.length) + ' more</div>';
        }

        return html;
    }

    $(document).on('mouseenter', '.slot-cell', function () {
        var $button = $(this);
        var slotId = parseInt($button.data('slot-id'), 10) || 0;
        var slotLabel = $button.data('slot-label') || '';
        var slotContext = $button.data('slot-context') || '';

        if (!slotId) {
            return;
        }

        if (slotProductsCache[slotId]) {
            showSlotPopover($button, buildSlotProductsContent(slotProductsCache[slotId], slotLabel, slotContext));
            return;
        }

        showSlotPopover($button, '<div class="text-muted fw-semibold fs-7">Loading products...</div>');

        $.ajax({
            url: slotProductsEndpoint,
            type: 'GET',
            dataType: 'json',
            data: {slot_id: slotId}
        }).done(function (response) {
            slotProductsCache[slotId] = response || {};
            if ($button.is(':hover')) {
                showSlotPopover($button, buildSlotProductsContent(slotProductsCache[slotId], slotLabel, slotContext));
            }
        }).fail(function () {
            if ($button.is(':hover')) {
                showSlotPopover($button, '<div class="text-danger fw-semibold fs-7">Unable to load products.</div>');
            }
        });
    });

    $(document).on('mouseleave', '.slot-cell', function () {
        disposeSlotPopover(this);
    });

    $(document).on('click', '.slot-cell', function () {
        hideAllSlotPopovers();

        var $btn      = $(this);
        var slotId    = $btn.data('slot-id');
        var label     = $btn.data('slot-label');
        var isFull    = $btn.data('is-full') == '1';
        var capacity  = $btn.data('capacity');
        var occupancy = $btn.data('occupancy');
        var zone      = $btn.data('zone');

        var available = capacity > 0 ? Math.max(0, capacity - occupancy) : '∞';

        $('#slotModalTitle').text(label);
        $('#slotModalZone').text(zone || '—');
        $('#slotModalCode').text(label);
        $('#slotModalOccupied').text(occupancy);
        $('#slotModalCapacity').text(capacity > 0 ? capacity : '∞');
        $('#slotModalStatus').html(
            isFull
                ? '<span class="badge badge-light-danger">{{ __("lang_v1.slot_full") }}</span>'
                : '<span class="badge badge-light-success">' + available + ' {{ __("lang_v1.slot_available") }}</span>'
        );
        $('#slotModalEditBtn').attr('href', '{{ route("storage-manager.slots.index") }}/' + slotId + '/edit');

        var modal = new bootstrap.Modal(document.getElementById('slotDetailModal'));
        modal.show();
    });
});
</script>
@endsection
