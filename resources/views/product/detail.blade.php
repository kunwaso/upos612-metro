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
@endsection
