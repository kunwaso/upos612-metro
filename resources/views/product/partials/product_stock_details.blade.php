@php
    $canDirectEditStock = auth()->user()->can('product.update')
        && (auth()->user()->can('product.opening_stock') || auth()->user()->can('stock_adjustment.create'));
@endphp

<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-condensed bg-gray">
                <thead>
                    <tr class="bg-green">
                        <th>SKU</th>
                        <th>@lang('business.product')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('sale.unit_price')</th>
                        <th>@lang('report.current_stock')</th>
                        <th>@lang('lang_v1.total_stock_price')</th>
                        <th>@lang('report.total_unit_sold')</th>
                        <th>@lang('lang_v1.total_unit_transfered')</th>
                        <th>@lang('lang_v1.total_unit_adjusted')</th>
                        @if($canDirectEditStock)
                            <th>@lang('messages.action')</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($product_stock_details as $product)
                        @php
                            $name = $product->product;
                            if ($product->type == 'variable') {
                                $name .= ' - ' . $product->product_variation . '-' . $product->variation_name;
                            }
                        @endphp
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $name }}</td>
                            <td>{{ $product->location_name }}</td>
                            <td>
                                <span class="display_currency" data-currency_symbol=true>{{ $product->unit_price ?? 0 }}</span>
                            </td>
                            <td>
                                <span data-is_quantity="true" class="display_currency" data-currency_symbol=false>{{ $product->stock ?? 0 }}</span> {{ $product->unit }}
                            </td>
                            <td>
                                <span class="display_currency" data-currency_symbol=true>{{ $product->unit_price * $product->stock }}</span>
                            </td>
                            <td>
                                <span data-is_quantity="true" class="display_currency" data-currency_symbol=false>{{ $product->total_sold ?? 0 }}</span> {{ $product->unit }}
                            </td>
                            <td>
                                <span data-is_quantity="true" class="display_currency" data-currency_symbol=false>{{ $product->total_transfered ?? 0 }}</span> {{ $product->unit }}
                            </td>
                            <td>
                                <span data-is_quantity="true" class="display_currency" data-currency_symbol=false>{{ $product->total_adjusted ?? 0 }}</span> {{ $product->unit }}
                            </td>
                            @if($canDirectEditStock)
                                <td>
                                    <button type="button"
                                        class="btn btn-sm btn-light-primary btn-open-direct-stock-modal"
                                        data-product-id="{{ (int) $product->product_id }}"
                                        data-variation-id="{{ (int) $product->variation_id }}"
                                        data-location-id="{{ (int) $product->location_id }}"
                                        data-location-name="{{ e($product->location_name) }}"
                                        data-product-name="{{ e($name) }}"
                                        data-current-stock="{{ (float) ($product->stock ?? 0) }}"
                                        data-unit="{{ e($product->unit) }}">
                                        @lang('messages.edit')
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canDirectEditStock)
    <div class="modal fade" id="directStockEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('stock_adjustment.stock_adjustment')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('messages.close')"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="direct_stock_product_id">
                    <input type="hidden" id="direct_stock_variation_id">
                    <input type="hidden" id="direct_stock_location_id">

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-gray-700">@lang('business.product')</label>
                        <div id="direct_stock_product_name" class="text-gray-900">-</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-gray-700">@lang('business.location')</label>
                        <div id="direct_stock_location_name" class="text-gray-900">-</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-gray-700">@lang('report.current_stock')</label>
                        <div id="direct_stock_current" class="text-gray-900">0</div>
                    </div>

                    <div class="mb-3">
                        <label for="direct_stock_target" class="form-label fw-semibold text-gray-700">@lang('report.current_stock')</label>
                        <input type="text" id="direct_stock_target" class="form-control input_number" required>
                        <div class="form-text">Set the final stock value for this location.</div>
                    </div>

                    <div class="mb-0">
                        <label for="direct_stock_reason" class="form-label fw-semibold text-gray-700">@lang('stock_adjustment.reason_for_stock_adjustment')</label>
                        <textarea id="direct_stock_reason" class="form-control" rows="3" maxlength="1000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="button" class="btn btn-primary" id="direct_stock_save_btn">@lang('messages.save')</button>
                </div>
            </div>
        </div>
    </div>
@endif
