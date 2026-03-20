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
    if ($stockDiv.length && $stockDiv.data('product_id')) {
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
