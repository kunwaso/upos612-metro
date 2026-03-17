{{-- Product detail: Prices tab – single/variable/combo product details (from view-modal) --}}
<div class="col-12">
    <div class="card card-flush">
        <div class="card-header">
            <h3 class="card-title fw-bold text-gray-900">@lang('product.prices')</h3>
            <div class="card-toolbar">
                @can('access_default_selling_price')
                <a href="{{ action([\App\Http\Controllers\ProductController::class, 'addSellingPrices'], [$product->id]) }}" class="btn btn-sm btn-light-primary me-2">@lang('lang_v1.add_selling_price_group_prices')</a>
                @endcan
                @if($product->type !== 'single')
                <a href="{{ action([\App\Http\Controllers\ProductController::class, 'viewGroupPrice'], [$product->id]) }}" class="btn btn-sm btn-light">@lang('lang_v1.group_prices')</a>
                @endif
            </div>
        </div>
        <div class="card-body pt-0">
            @if($product->type == 'single')
                @include('product.partials.single_product_details')
            @elseif($product->type == 'variable')
                @include('product.partials.variable_product_details')
            @elseif($product->type == 'combo')
                @include('product.partials.combo_product_details')
            @endif
        </div>
    </div>
</div>
