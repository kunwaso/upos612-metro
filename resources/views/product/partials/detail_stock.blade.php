{{-- Product detail: Stock tab – rack/location details + per-location stock (AJAX) --}}
<div class="col-12">
    <div class="card card-flush">
        <div class="card-header">
            <h3 class="card-title fw-bold text-gray-900">@lang('product.stock')</h3>
        </div>
        <div class="card-body pt-0">
            @if(!empty($details) && count($details) > 0 && (($enableRacks ?? false) || ($enableRow ?? false) || ($enablePosition ?? false)))
            <div class="mb-5">
                <div class="fw-semibold fs-6 text-gray-700 mb-3">@lang('lang_v1.rack_details')</div>
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-gray-800 border-bottom border-gray-200">
                                <th class="min-w-150px">@lang('business.location')</th>
                                @if($enableRacks ?? false)
                                    <th class="min-w-100px">@lang('lang_v1.rack')</th>
                                @endif
                                @if($enableRow ?? false)
                                    <th class="min-w-100px">@lang('lang_v1.row')</th>
                                @endif
                                @if($enablePosition ?? false)
                                    <th class="min-w-100px">@lang('lang_v1.position')</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($details as $detail)
                                <tr>
                                    <td>{{ $detail->name ?? '—' }}</td>
                                    @if($enableRacks ?? false)
                                        <td>{{ $detail->rack ?? '—' }}</td>
                                    @endif
                                    @if($enableRow ?? false)
                                        <td>{{ $detail->row ?? '—' }}</td>
                                    @endif
                                    @if($enablePosition ?? false)
                                        <td>{{ $detail->position ?? '—' }}</td>
                                    @endif
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
