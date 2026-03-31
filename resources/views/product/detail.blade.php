@extends('layouts.app')

@section('title', __('product.view_product') . ' - ' . $product->name)

@section('content')
<div class="product-detail-page no-print">
@include('product._product_header')

@if(session('status'))
    <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-5">
        {{ session('status.msg') }}
    </div>
@endif

<div class="row gx-6 gx-xl-9">
    @if(($activeTab ?? '') === 'overview')
        @include('product.partials.detail_overview')
    @elseif(($activeTab ?? '') === 'stock')
        @include('product.partials.detail_stock')
    @elseif(($activeTab ?? '') === 'prices')
        @include('product.partials.detail_prices')
    @elseif(($activeTab ?? '') === 'quotes')
        @include('product.partials.detail_quotes')
    @elseif(($activeTab ?? '') === 'files')
        @include('product.partials.detail_files')
    @elseif(($activeTab ?? '') === 'contacts')
        @include('product.partials.detail_contacts')
    @elseif(($activeTab ?? '') === 'activity')
        @include('product.partials.detail_activity')
    @else
        @include('product.partials.detail_overview')
    @endif
</div>
</div>
@endsection

@section('javascript')
<script>
$(function() {
    var $stockDiv = $('#view_product_stock_details');
    var directStockAdjustUrl = "{{ route('product.detail.stock.adjust', ['id' => $product->id]) }}";
    var directStockModal = null;

    function loadProductStockDetails() {
        $.ajax({
            url: "{{ action([\App\Http\Controllers\ReportController::class, 'getStockReport']) }}" + '?for=view_product&product_id=' + $stockDiv.data('product_id'),
            dataType: 'html',
            success: function(result) {
                $stockDiv.html(result);
                if (typeof __currency_convert_recursively === 'function') {
                    __currency_convert_recursively($stockDiv);
                }
            },
        });
    }

    if ($stockDiv.length && $stockDiv.data('product_id')) {
        loadProductStockDetails();
    }

    $(document).on('click', '.btn-open-direct-stock-modal', function () {
        var modalElement = document.getElementById('directStockEditModal');
        if (!modalElement) {
            return;
        }

        var $btn = $(this);
        var currentStock = parseFloat($btn.data('current-stock')) || 0;
        var unit = $btn.data('unit') || '';
        var productName = $btn.data('product-name') || '-';
        var locationName = $btn.data('location-name') || '-';

        $('#direct_stock_product_id').val($btn.data('product-id'));
        $('#direct_stock_variation_id').val($btn.data('variation-id'));
        $('#direct_stock_location_id').val($btn.data('location-id'));
        $('#direct_stock_product_name').text(productName);
        $('#direct_stock_location_name').text(locationName);
        $('#direct_stock_current').text(currentStock + (unit ? (' ' + unit) : ''));
        $('#direct_stock_target').val(currentStock);
        $('#direct_stock_reason').val('');

        if (!directStockModal || directStockModal._element !== modalElement) {
            directStockModal = (typeof bootstrap.Modal.getOrCreateInstance === 'function')
                ? bootstrap.Modal.getOrCreateInstance(modalElement)
                : new bootstrap.Modal(modalElement);
        }
        directStockModal.show();
    });

    $(document).on('click', '#direct_stock_save_btn', function () {
        var $saveBtn = $(this);
        var variationId = $('#direct_stock_variation_id').val();
        var locationId = $('#direct_stock_location_id').val();
        var reason = ($('#direct_stock_reason').val() || '').trim();

        var targetStock = (typeof __read_number === 'function')
            ? parseFloat(__read_number($('#direct_stock_target')))
            : parseFloat($('#direct_stock_target').val());

        if (!variationId || !locationId || isNaN(targetStock) || targetStock < 0 || !reason) {
            if (typeof toastr !== 'undefined') {
                toastr.error("@lang('messages.required')");
            }
            return;
        }

        var originalBtnHtml = $saveBtn.html();
        $saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: directStockAdjustUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                variation_id: variationId,
                location_id: locationId,
                target_stock: targetStock,
                reason: reason
            },
            success: function (res) {
                if (res && res.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(res.msg || "@lang('lang_v1.success')");
                    }
                    if (directStockModal) {
                        directStockModal.hide();
                    }
                    loadProductStockDetails();
                } else if (typeof toastr !== 'undefined') {
                    toastr.error((res && res.msg) ? res.msg : "@lang('messages.something_went_wrong')");
                }
            },
            error: function (xhr) {
                var message = "@lang('messages.something_went_wrong')";
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.msg) {
                        message = xhr.responseJSON.msg;
                    } else if (xhr.responseJSON.errors) {
                        var firstField = Object.keys(xhr.responseJSON.errors)[0];
                        if (firstField && xhr.responseJSON.errors[firstField][0]) {
                            message = xhr.responseJSON.errors[firstField][0];
                        }
                    }
                }

                if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                }
            },
            complete: function () {
                $saveBtn.prop('disabled', false).html(originalBtnHtml);
            }
        });
    });
});
</script>

@if($activeTab === 'stock')
@can('storage_manager.manage')
<script>
$(function () {
    var currentProductId  = null;
    var currentLocationId = null;

    $(document).on('click', '.btn-change-slot', function () {
        currentProductId  = $(this).data('product-id');
        currentLocationId = $(this).data('location-id');

        $('#changeSlotLoading').removeClass('d-none');
        $('#changeSlotContent').addClass('d-none');
        $('#changeSlotEmpty').addClass('d-none');
        $('#changeSlotSave').prop('disabled', true);

        var modal = new bootstrap.Modal(document.getElementById('changeSlotModal'));
        modal.show();

        $.getJSON('{{ route("storage-manager.available-slots") }}', { location_id: currentLocationId }, function (res) {
            $('#changeSlotLoading').addClass('d-none');
            var slots = res.slots || {};
            var keys  = Object.keys(slots);

            if (keys.length === 0) {
                $('#changeSlotEmpty').removeClass('d-none');
                return;
            }

            var $sel = $('#changeSlotSelect').empty().append('<option value="">— @lang("messages.select") —</option>');
            $.each(slots, function (id, label) {
                $sel.append($('<option>').val(id).text(label));
            });

            $('#changeSlotContent').removeClass('d-none');
        });
    });

    $('#changeSlotSelect').on('change', function () {
        $('#changeSlotSave').prop('disabled', !$(this).val());
    });

    $('#changeSlotSave').on('click', function () {
        var slotId = $('#changeSlotSelect').val();
        if (!slotId || !currentProductId) return;

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '{{ route("storage-manager.assign-slot") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', product_id: currentProductId, slot_id: slotId },
            success: function (res) {
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('changeSlotModal')).hide();
                    location.reload();
                }
            },
            error: function () {
                $('#changeSlotSave').prop('disabled', false).text('@lang("lang_v1.assign_slot")');
            }
        });
    });
});
</script>
@endcan
@endif
@endsection
