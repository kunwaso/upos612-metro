<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-5" id="purchase_entry_table">
        <thead>
            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                <th>#</th>
                <th>@lang('product.product_name')</th>
                <th>
                    @if(empty($is_purchase_order))
                        @lang('purchase.purchase_quantity')
                    @else
                        @lang('lang_v1.order_quantity')
                    @endif
                </th>
                <th>@lang('lang_v1.unit_cost_before_discount')</th>
                <th>@lang('lang_v1.discount_percent')</th>
                <th>@lang('purchase.unit_cost_before_tax')</th>
                <th class="{{ $ui_flags['hide_tax_class'] }}">@lang('purchase.subtotal_before_tax')</th>
                <th class="{{ $ui_flags['hide_tax_class'] }}">@lang('purchase.product_tax')</th>
                <th class="{{ $ui_flags['hide_tax_class'] }}">@lang('purchase.net_cost')</th>
                <th>@lang('purchase.line_total')</th>
                <th class="@if(empty($ui_flags['show_editing_product_from_purchase']) || !empty($is_purchase_order)) hide @endif">
                    @lang('lang_v1.profit_margin')
                </th>
                @if(empty($is_purchase_order))
                    <th>
                        @lang('purchase.unit_selling_price')
                        <small>(@lang('product.inc_of_tax'))</small>
                    </th>
                    @if(!empty($ui_flags['show_lot_number']))
                        <th>@lang('lang_v1.lot_number')</th>
                    @endif
                    @if(!empty($ui_flags['show_product_expiry']))
                        <th>@lang('product.mfg_date') / @lang('product.exp_date')</th>
                    @endif
                @endif
                <th><i class="fa fa-trash" aria-hidden="true"></i></th>
            </tr>
        </thead>
        <tbody>
            @include('purchase.partials.purchase_entry_row', [
                'row_models' => $row_models,
                'next_row_count' => $next_row_count,
                'suppress_row_count' => true,
            ])
        </tbody>
    </table>
</div>
<input type="hidden" id="row_count" value="{{ $next_row_count }}">
