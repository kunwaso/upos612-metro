@foreach($row_models as $row)
    <tr
        @if(!empty($row['data_purchase_order_id'])) data-purchase_order_id="{{ $row['data_purchase_order_id'] }}" @endif
        @if(!empty($row['data_purchase_requisition_id'])) data-purchase_requisition_id="{{ $row['data_purchase_requisition_id'] }}" @endif
    >
        <td><span class="sr_number"></span></td>
        <td>
            {{ $row['product_display_name'] }}
            @if(!empty($row['variation_display']))
                <br>
                <small class="text-muted">{{ $row['variation_display'] }}</small>
            @endif
            @if(!empty($row['stock_display']))
                <br>
                <small class="text-muted" style="white-space: nowrap;">{{ $row['stock_display'] }}</small>
            @endif
        </td>
        <td>
            @if(!empty($row['purchase_order_line_id']))
                {!! Form::hidden('purchases[' . $row['row_index'] . '][purchase_order_line_id]', $row['purchase_order_line_id']) !!}
            @endif
            @if(!empty($row['purchase_requisition_line_id']))
                {!! Form::hidden('purchases[' . $row['row_index'] . '][purchase_requisition_line_id]', $row['purchase_requisition_line_id']) !!}
            @endif
            @if(!empty($row['purchase_line_id']))
                {!! Form::hidden('purchases[' . $row['row_index'] . '][purchase_line_id]', $row['purchase_line_id']) !!}
            @endif

            {!! Form::hidden('purchases[' . $row['row_index'] . '][product_id]', $row['product_id']) !!}
            {!! Form::hidden('purchases[' . $row['row_index'] . '][variation_id]', $row['variation_id'], ['class' => 'hidden_variation_id']) !!}

            <input type="text"
                name="purchases[{{ $row['row_index'] }}][quantity]"
                value="{{ $row['quantity_formatted'] }}"
                class="form-control form-control-sm purchase_quantity input_number mousetrap"
                required
                data-rule-abs_digit="{{ $row['quantity_abs_digit'] ? 'true' : 'false' }}"
                data-msg-abs_digit="{{ __('lang_v1.decimal_value_not_allowed') }}"
                @if(!is_null($row['max_quantity']))
                    data-rule-max-value="{{ $row['max_quantity'] }}"
                    data-msg-max-value="{{ $row['max_quantity_message'] }}"
                @endif
            >

            <input type="hidden" class="base_unit_cost" value="{{ $row['base_unit_cost'] }}">
            <input type="hidden" class="base_unit_selling_price" value="{{ $row['base_unit_selling_price'] }}">
            <input type="hidden" name="purchases[{{ $row['row_index'] }}][product_unit_id]" value="{{ $row['product_unit_id'] }}">

            @if(!empty($row['sub_units']))
                <br>
                <select name="purchases[{{ $row['row_index'] }}][sub_unit_id]" class="form-select form-select-sm sub_unit">
                    @foreach($row['sub_units'] as $sub_unit)
                        <option
                            value="{{ $sub_unit['id'] }}"
                            data-multiplier="{{ $sub_unit['multiplier'] }}"
                            @if(!empty($sub_unit['selected'])) selected @endif
                        >
                            {{ $sub_unit['name'] }}
                        </option>
                    @endforeach
                </select>
            @else
                {{ $row['unit_short_name'] }}
            @endif

            @if(!empty($row['show_second_unit']))
                <br>
                <span style="white-space: nowrap;">
                    @lang('lang_v1.quantity_in_second_unit', ['unit' => $row['second_unit_name']])*:
                </span>
                <br>
                <input
                    type="text"
                    name="purchases[{{ $row['row_index'] }}][secondary_unit_quantity]"
                    value="{{ $row['secondary_unit_quantity'] }}"
                    class="form-control form-control-sm input_number"
                    required
                >
            @endif
        </td>
        <td>
            {!! Form::text(
                'purchases[' . $row['row_index'] . '][pp_without_discount]',
                $row['pp_without_discount_formatted'],
                ['class' => 'form-control form-control-sm purchase_unit_cost_without_discount input_number', 'required']
            ) !!}

            @if(!empty($row['previous_pp_without_discount']))
                <br>
                <small class="text-muted">
                    @lang('lang_v1.prev_unit_price'): {{ $row['previous_pp_without_discount'] }}
                </small>
            @endif
        </td>
        <td>
            {!! Form::text(
                'purchases[' . $row['row_index'] . '][discount_percent]',
                $row['discount_percent_formatted'],
                ['class' => 'form-control form-control-sm inline_discounts input_number', 'required']
            ) !!}

            @if(!empty($row['previous_discount_percent']))
                <br>
                <small class="text-muted">
                    @lang('lang_v1.prev_discount'): {{ $row['previous_discount_percent'] }}%
                </small>
            @endif
        </td>
        <td>
            {!! Form::text(
                'purchases[' . $row['row_index'] . '][purchase_price]',
                $row['purchase_price_formatted'],
                ['class' => 'form-control form-control-sm purchase_unit_cost input_number', 'required']
            ) !!}
        </td>
        <td class="{{ $row['hide_tax_class'] }}">
            <span class="row_subtotal_before_tax">{{ $row['row_subtotal_before_tax_formatted'] }}</span>
            <input type="hidden" class="row_subtotal_before_tax_hidden" value="{{ $row['row_subtotal_before_tax'] }}">
        </td>
        <td class="{{ $row['hide_tax_class'] }}">
            <div class="input-group">
                <select
                    name="purchases[{{ $row['row_index'] }}][purchase_line_tax_id]"
                    class="form-select form-select-sm purchase_line_tax_id"
                    placeholder="'Please Select'"
                >
                    @foreach($row['tax_options'] as $tax_option)
                        <option
                            value="{{ $tax_option['id'] }}"
                            data-tax_amount="{{ $tax_option['amount'] }}"
                            @if(!empty($tax_option['selected'])) selected @endif
                        >
                            {{ $tax_option['name'] }}
                        </option>
                    @endforeach
                </select>
                {!! Form::hidden(
                    'purchases[' . $row['row_index'] . '][item_tax]',
                    $row['item_tax'],
                    ['class' => 'purchase_product_unit_tax']
                ) !!}
                <span class="input-group-text purchase_product_unit_tax_text">
                    {{ $row['item_tax_formatted'] }}
                </span>
            </div>
        </td>
        <td class="{{ $row['hide_tax_class'] }}">
            {!! Form::text(
                'purchases[' . $row['row_index'] . '][purchase_price_inc_tax]',
                $row['purchase_price_inc_tax_formatted'],
                ['class' => 'form-control form-control-sm purchase_unit_cost_after_tax input_number', 'required']
            ) !!}
        </td>
        <td>
            <span class="row_subtotal_after_tax">{{ $row['row_subtotal_after_tax_formatted'] }}</span>
            <input type="hidden" class="row_subtotal_after_tax_hidden" value="{{ $row['row_subtotal_after_tax'] }}">
        </td>
        <td class="@if(empty($row['show_profit_margin'])) hide @endif">
            {!! Form::text(
                'purchases[' . $row['row_index'] . '][profit_percent]',
                $row['profit_percent_formatted'],
                ['class' => 'form-control form-control-sm input_number profit_percent', 'required']
            ) !!}
        </td>

        @if(!empty($row['show_sell_price_column']))
            <td>
                @if(!empty($row['show_sell_price_input']))
                    {!! Form::text(
                        'purchases[' . $row['row_index'] . '][default_sell_price]',
                        $row['default_sell_price_formatted'],
                        ['class' => 'form-control form-control-sm input_number default_sell_price', 'required']
                    ) !!}
                @else
                    {{ $row['default_sell_price_formatted'] }}
                @endif
            </td>
            @if(!empty($row['show_lot_number']))
                <td>
                    {!! Form::text(
                        'purchases[' . $row['row_index'] . '][lot_number]',
                        $row['lot_number'],
                        ['class' => 'form-control form-control-sm']
                    ) !!}
                </td>
            @endif
            @if(!empty($row['show_product_expiry']))
                <td style="text-align: left;">
                    <input type="hidden" class="row_product_expiry" value="{{ $row['expiry_period'] }}">
                    <input type="hidden" class="row_product_expiry_type" value="{{ $row['expiry_period_type'] }}">

                    <b class="@if(empty($row['show_mfg_date'])) hide @endif"><small>@lang('product.mfg_date'):</small></b>
                    <div class="input-group @if(empty($row['show_mfg_date'])) hide @endif">
                        <span class="input-group-text">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text(
                            'purchases[' . $row['row_index'] . '][mfg_date]',
                            $row['mfg_date'],
                            ['class' => 'form-control form-control-sm expiry_datepicker mfg_date', 'readonly']
                        ) !!}
                    </div>
                    <b><small>@lang('product.exp_date'):</small></b>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text(
                            'purchases[' . $row['row_index'] . '][exp_date]',
                            $row['exp_date'],
                            ['class' => 'form-control form-control-sm expiry_datepicker exp_date', 'readonly']
                        ) !!}
                    </div>
                </td>
            @endif
        @endif
        <td>
            <i class="fa fa-times remove_purchase_entry_row text-danger" title="@lang('messages.remove')" style="cursor:pointer;"></i>
        </td>
    </tr>
@endforeach

@if(empty($suppress_row_count))
    <input type="hidden" id="row_count" value="{{ $next_row_count ?? 0 }}">
@endif
