<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary no-print" data-bs-dismiss="modal" data-dismiss="modal" aria-label="@lang('messages.close')"><i class="ki-duotone ki-cross fs-2x"><span class="path1"></span><span class="path2"></span></i></button>
            <h4 class="modal-title">
                @lang('lang_v1.warehouse_document_details')
                (<b>@lang('sale.ref_no'):</b> {{ $document->document_no }})
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>@lang('lang_v1.type'):</strong> {{ ucwords(str_replace('_', ' ', (string) $document->document_type)) }}<br>
                    <strong>@lang('lang_v1.status'):</strong> {{ $document->status ?: '-' }}<br>
                    <strong>@lang('lang_v1.workflow_state'):</strong> {{ $document->workflow_state ?: '-' }}<br>
                    <strong>@lang('business.location'):</strong> {{ $locationName }}<br>
                    <strong>@lang('lang_v1.storage_area'):</strong> {{ optional($document->area)->name ?: '-' }}
                </div>
                <div class="col-md-6">
                    <strong>@lang('lang_v1.source'):</strong> {{ $document->source_ref ?: ((string) $document->source_type ?: '-') }}<br>
                    <strong>@lang('lang_v1.vas_sync'):</strong> {{ $document->sync_status ?: 'not_required' }}<br>
                    <strong>@lang('lang_v1.approval_status'):</strong> {{ $document->approval_status ?: 'not_required' }}<br>
                    <strong>@lang('messages.date'):</strong>
                    @if(!empty($document->created_at))
                        {{ format_datetime_value($document->created_at) }}
                    @else
                        -
                    @endif
                    <br>
                    <strong>@lang('lang_v1.notes'):</strong> {{ $document->notes ?: '-' }}
                </div>
            </div>

            @if($workbench)
                <div class="row mt-4">
                    <div class="col-md-12">
                        @if($workbench['is_modal'])
                            <a href="#"
                                class="btn-modal btn btn-sm btn-primary"
                                data-container=".view_modal"
                                data-href="{{ $workbench['url'] }}">
                                @lang('lang_v1.open_workbench')
                            </a>
                        @else
                            <a href="{{ $workbench['url'] }}" class="btn btn-sm btn-primary">
                                @lang('lang_v1.open_workbench')
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if(!empty($vasLink))
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5 class="mb-3">@lang('lang_v1.vas_document_link')</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('sale.ref_no')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th>@lang('lang_v1.date')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $vasLink->linked_ref ?: (!empty($vasLink->linked_id) ? ('#' . $vasLink->linked_id) : '-') }}</td>
                                    <td>{{ $vasLink->sync_status ?: '-' }}</td>
                                    <td>{{ optional($vasLink->synced_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    <td>
                                        @if($vasAction)
                                            <a href="{{ $vasAction['url'] }}" class="btn btn-sm btn-light-primary">@lang('lang_v1.open_vas_document')</a>
                                        @else
                                            <span class="text-muted fs-8">-</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="row mt-4">
                <div class="col-md-12">
                    <h5 class="mb-3">@lang('lang_v1.document_lines')</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>@lang('product.product')</th>
                                    <th>@lang('sale.qty')</th>
                                    <th>@lang('lang_v1.executed_qty')</th>
                                    <th>@lang('lang_v1.source_slot')</th>
                                    <th>@lang('lang_v1.destination_slot')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th>@lang('lang_v1.actor')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($document->lines as $line)
                                    <tr>
                                        <td>{{ $line->line_no }}</td>
                                        <td>
                                            {{ optional($line->product)->name ?: data_get($line->meta, 'product_label', '-') }}
                                            <div class="text-muted small">{{ optional($line->variation)->sub_sku ?: ($line->lot_number ?: '-') }}</div>
                                        </td>
                                        <td>{{ format_quantity_value($line->expected_qty ?? 0) }}</td>
                                        <td>{{ format_quantity_value($line->executed_qty ?? 0) }}</td>
                                        <td>{{ optional($line->fromSlot)->code ?: '-' }}</td>
                                        <td>{{ optional($line->toSlot)->code ?: '-' }}</td>
                                        <td>{{ $line->result_status ?: ($line->inventory_status ?: '-') }}</td>
                                        <td>
                                            @php
                                                $taskNames = $line->tasks->pluck('assignee.user_full_name')->filter()->unique()->values();
                                            @endphp
                                            {{ $taskNames->isNotEmpty() ? $taskNames->implode(', ') : __('lang_v1.system') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">@lang('lang_v1.no_document_lines_yet')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->links as $link)
                                    @php
                                        $action = $linkedRecordActions[$link->id] ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ strtoupper((string) $link->linked_system) }}</td>
                                        <td>{{ $link->linked_type ?: '-' }}</td>
                                        <td>{{ $link->linked_ref ?: (!empty($link->linked_id) ? ('#' . $link->linked_id) : '-') }}</td>
                                        <td>{{ $link->sync_status ?: '-' }}</td>
                                        <td>
                                            @if($action)
                                                @if($action['is_modal'])
                                                    <a href="#"
                                                        class="btn-modal btn btn-sm btn-light-primary"
                                                        data-container=".view_modal"
                                                        data-href="{{ $action['url'] }}">
                                                        @lang('messages.view')
                                                    </a>
                                                @else
                                                    <a href="{{ $action['url'] }}" class="btn btn-sm btn-light-primary">
                                                        @lang('messages.view')
                                                    </a>
                                                @endif
                                            @else
                                                <span class="text-muted fs-8">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($document->syncLogs->isNotEmpty())
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5 class="mb-3">@lang('lang_v1.recent_sync_log')</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.system')</th>
                                    <th>@lang('lang_v1.action')</th>
                                    <th>@lang('lang_v1.status')</th>
                                    <th>@lang('lang_v1.actor')</th>
                                    <th>@lang('lang_v1.message')</th>
                                    <th>@lang('lang_v1.date')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->syncLogs as $log)
                                    <tr>
                                        <td>{{ strtoupper((string) $log->linked_system) }}</td>
                                        <td>{{ $log->action ?: '-' }}</td>
                                        <td>{{ $log->status ?: '-' }}</td>
                                        <td>{{ $log->actor_label }}</td>
                                        <td>{{ $log->message ?: '-' }}</td>
                                        <td>{{ optional($log->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
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

