<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary no-print" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')"><i class="ki-duotone ki-cross fs-2x"><span class="path1"></span><span class="path2"></span></i></button>
            <h4 class="modal-title" id="modalTitle">
                @lang('lang_v1.purchasing_advisory_details')
                (<b>@lang('lang_v1.advisory_document_no'):</b> {{ $document->document_no }})
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>@lang('lang_v1.status'):</strong> {{ $document->status ?: '-' }}<br>
                    <strong>@lang('lang_v1.workflow_state'):</strong> {{ $document->workflow_state ?: '-' }}<br>
                    <strong>@lang('business.location'):</strong> {{ $locationName ?? ('#' . $document->location_id) }}<br>
                    <strong>@lang('lang_v1.storage_area'):</strong> {{ optional($document->area)->name ?: '-' }}
                </div>
                <div class="col-md-6">
                    <strong>@lang('product.product'):</strong> {{ data_get($document->meta, 'product_label', '-') }}<br>
                    <strong>@lang('product.sku'):</strong> {{ data_get($document->meta, 'sku', '-') }}<br>
                    <strong>@lang('lang_v1.external_shortage_qty'):</strong> {{ format_quantity_value(data_get($document->meta, 'external_shortage_qty', 0)) }}<br>
                    <strong>@lang('lang_v1.requested_qty'):</strong> {{ format_quantity_value(data_get($document->meta, 'requested_qty', 0)) }}
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.source_slot')</th>
                                <th>@lang('lang_v1.destination_slot')</th>
                                <th>@lang('lang_v1.notes')</th>
                                <th>@lang('messages.date')</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ data_get($document->meta, 'source_label', '-') }}</td>
                                <td>{{ data_get($document->meta, 'destination_label', '-') }}</td>
                                <td>{{ $document->notes ?: '-' }}</td>
                                <td>
                                    @if(!empty($document->created_at))
                                        {{ format_datetime_value($document->created_at) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if($purchaseRequisitionLink)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <strong>@lang('lang_v1.purchase_requisition'):</strong>
                        <a href="#"
                            class="btn-modal text-primary"
                            data-container=".view_modal"
                            data-href="{{ action([\App\Http\Controllers\PurchaseRequisitionController::class, 'show'], [$purchaseRequisitionLink->linked_id]) }}">
                            {{ $purchaseRequisitionLink->linked_ref ?: ('#' . $purchaseRequisitionLink->linked_id) }}
                        </a>
                    </div>
                </div>
            @endif

            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">@lang('lang_v1.advisory_activity_timeline')</h5>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.system')</th>
                                <th>@lang('lang_v1.action')</th>
                                <th>@lang('lang_v1.status')</th>
                                <th>@lang('lang_v1.actor')</th>
                                <th>@lang('lang_v1.message')</th>
                                <th>@lang('messages.date')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($document->syncLogs as $syncLog)
                                <tr>
                                    <td>{{ strtoupper((string) $syncLog->linked_system) }}</td>
                                    <td>{{ $syncLog->action ?: '-' }}</td>
                                    <td>{{ $syncLog->status ?: '-' }}</td>
                                    <td>{{ $syncLog->actor_label }}</td>
                                    <td>{{ $syncLog->message ?: '-' }}</td>
                                    <td>
                                        @if(!empty($syncLog->created_at))
                                            {{ format_datetime_value($syncLog->created_at) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">@lang('lang_v1.no_advisory_activity_yet')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($document->links->isNotEmpty())
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5 class="mb-3">@lang('lang_v1.linked_records')</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.system')</th>
                                    <th>@lang('lang_v1.type')</th>
                                    <th>@lang('sale.ref_no')</th>
                                    <th>@lang('lang_v1.status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->links as $link)
                                    <tr>
                                        <td>{{ strtoupper((string) $link->linked_system) }}</td>
                                        <td>{{ $link->linked_type ?: '-' }}</td>
                                        <td>{{ $link->linked_ref ?: ('#' . $link->linked_id) }}</td>
                                        <td>{{ $link->sync_status ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-light no-print" data-bs-dismiss="modal" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

