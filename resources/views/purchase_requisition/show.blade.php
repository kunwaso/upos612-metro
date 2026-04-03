<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary no-print" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')"><i class="ki-duotone ki-cross fs-2x"><span class="path1"></span><span class="path2"></span></i></button>
            <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.purchase_requisition_details') (<b>@lang('purchase.ref_no'):</b> #{{ $purchase->ref_no }})
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>@lang('messages.location'): </strong> {{$purchase->location->name}}<br>
                    <strong>@lang('purchase.ref_no'): </strong> {{$purchase->ref_no}}
                </div>
                <div class="col-md-6">
                    <strong>@lang('lang_v1.required_by_date'): </strong> @if(!empty($purchase->delivery_date)){{ format_datetime_value($purchase->delivery_date) }}@endif <br>
                    <strong>@lang('lang_v1.added_by'): </strong> {{$purchase->sales_person->user_full_name}}
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-md-12">
                    <table class="table bg-gray">
                        <thead>
                            <tr class="bg-green">
                                <th>@lang('sale.product')</th>
                                <th>@lang('lang_v1.required_quantity')</th>
                                <th>@lang( 'lang_v1.quantity_remaining' )</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->purchase_lines as $purchase_line)
                                <tr>
                                <td>
                                    {{$purchase_line->product->name}}
                                    @if($purchase_line->product->type == 'single')
                                     ({{$purchase_line->product->sku}})
                                    @else
                                        - {{$purchase_line->variations->product_variation->name}} - {{$purchase_line->variations->name}} ({{$purchase_line->variations->sub_sku}})
                                    @endif
                                </td>
                                <td>
                                    {{ format_quantity_value($purchase_line->quantity) }} {{$purchase_line->product->unit->short_name}}

                                    @if(!empty($purchase_line->product->second_unit) && !empty($purchase_line->secondary_unit_quantity))
                                        <br>
                                        {{ format_quantity_value($purchase_line->secondary_unit_quantity) }} {{$purchase_line->product->second_unit->short_name}}
                                    @endif
                                </td>
                                <td>{{ format_quantity_value($purchase_line->quantity - $purchase_line->po_quantity_purchased) }} {{$purchase_line->product->unit->short_name}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if(!empty($advisory_links) && $advisory_links->count() > 0)
                <div class="row mt-5">
                    <div class="col-md-12">
                        <h5 class="mb-3">@lang('lang_v1.warehouse_advisories')</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.advisory_document_no')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('lang_v1.notes')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($advisory_links as $advisory_link)
                                    <tr>
                                        <td class="fw-semibold">
                                            @if(auth()->user()->can('storage_manager.view'))
                                                <a href="#"
                                                    class="btn-modal text-primary"
                                                    data-container=".view_modal"
                                                    data-href="{{ route('storage-manager.planning.show', $advisory_link->document_id) }}">
                                                    {{ optional($advisory_link->document)->document_no ?: ($advisory_link->linked_ref ?? '-') }}
                                                </a>
                                            @else
                                                {{ optional($advisory_link->document)->document_no ?: ($advisory_link->linked_ref ?? '-') }}
                                            @endif
                                        </td>
                                        <td>{{ optional($advisory_link->document)->status ?: '-' }}</td>
                                        <td>
                                            @if(!empty(optional($advisory_link->document)->created_at))
                                                {{ format_datetime_value(optional($advisory_link->document)->created_at) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ data_get($advisory_link->meta, 'requested_qty') ? __('lang_v1.requested_qty') . ': ' . format_quantity_value(data_get($advisory_link->meta, 'requested_qty')) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-light no-print" data-bs-dismiss="modal" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
  </div>
</div>


