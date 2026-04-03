@extends('layouts.app')

@section('title', __('vasaccounting::lang.views.inventory.recent_documents.title'))

@section('content')
    @php
        $document = $inventoryDocument;
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.views.inventory.recent_documents.title'),
        'subtitle' => $document->document_no,
    ])

    @if (session('status.msg'))
        <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} d-flex align-items-start gap-3 mb-8">
            <i class="fas fa-check-circle mt-1"></i>
            <div>{{ session('status.msg') }}</div>
        </div>
    @endif

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fs-7 fw-semibold">{{ __('vasaccounting::lang.views.shared.document') }}</div><div class="text-gray-900 fw-bold fs-3">{{ $document->document_no }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fs-7 fw-semibold">{{ __('vasaccounting::lang.views.shared.type') }}</div><div class="text-gray-900 fw-bold fs-3">{{ $vasAccountingUtil->documentTypeLabel((string) $document->document_type) }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fs-7 fw-semibold">{{ __('vasaccounting::lang.views.shared.status') }}</div><div class="text-gray-900 fw-bold fs-3">{{ $vasAccountingUtil->documentStatusLabel((string) $document->status) }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fs-7 fw-semibold">{{ __('vasaccounting::lang.views.shared.date') }}</div><div class="text-gray-900 fw-bold fs-3">{{ optional($document->posting_date)->format('Y-m-d') ?: '-' }}</div></div></div></div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-body py-5 d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div class="d-flex flex-wrap gap-3">
                <span class="badge badge-light-primary">{{ __('vasaccounting::lang.views.shared.branch') }}: {{ optional(optional($document->warehouse)->businessLocation)->name ?: '-' }}</span>
                <span class="badge badge-light-info">{{ __('vasaccounting::lang.views.inventory.document_form.warehouse') }}: {{ optional($document->warehouse)->code ?: '-' }}</span>
                <span class="badge badge-light-secondary">{{ __('vasaccounting::lang.views.inventory.document_form.destination_warehouse') }}: {{ optional($document->destinationWarehouse)->code ?: '-' }}</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('vasaccounting.inventory.index') }}" class="btn btn-sm btn-light">{{ __('vasaccounting::lang.actions.open_register') }}</a>
                @if (in_array($document->status, ['draft', 'pending_approval', 'approved'], true))
                    <form method="POST" action="{{ route('vasaccounting.inventory.documents.post', $document->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light-primary">{{ $vasAccountingUtil->actionLabel('post') }}</button>
                    </form>
                @elseif ($document->status === 'posted' && optional($document->postedVoucher)->status === 'posted')
                    <form method="POST" action="{{ route('vasaccounting.inventory.documents.reverse', $document->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light-danger">{{ $vasAccountingUtil->actionLabel('reverse') }}</button>
                    </form>
                @elseif ($document->status === 'posted')
                    <span class="text-warning fs-8">{{ __('vasaccounting::lang.inventory_reverse_requires_posted_voucher') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.shared.description') }}</div></div>
                <div class="card-body">
                    <div class="text-gray-800 mb-4">{{ $document->description ?: '-' }}</div>
                    <div class="row g-3">
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.views.shared.reference') }}</div><div class="fw-semibold">{{ $document->reference ?: '-' }}</div></div>
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.views.shared.source') }}</div><div class="fw-semibold">{{ $document->external_reference ?: '-' }}</div></div>
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.views.inventory.document_form.document_date') }}</div><div class="fw-semibold">{{ optional($document->document_date)->format('Y-m-d') ?: '-' }}</div></div>
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.views.inventory.document_form.posting_date') }}</div><div class="fw-semibold">{{ optional($document->posting_date)->format('Y-m-d') ?: '-' }}</div></div>
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.views.shared.voucher') }}</div><div class="fw-semibold">
                            @if($document->postedVoucher)
                                <a href="{{ route('vasaccounting.vouchers.show', $document->postedVoucher->id) }}">{{ $document->postedVoucher->voucher_no }}</a>
                            @else
                                -
                            @endif
                        </div></div>
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.actions.reverse') }}</div><div class="fw-semibold">
                            @if($document->reversalVoucher)
                                <a href="{{ route('vasaccounting.vouchers.show', $document->reversalVoucher->id) }}">{{ $document->reversalVoucher->voucher_no }}</a>
                            @else
                                -
                            @endif
                        </div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.reconciliation.title') }}</div></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.views.shared.account') }}</div><div class="fw-semibold">{{ optional($document->offsetAccount)->account_name ?: '-' }}</div></div>
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.views.shared.period') }}</div><div class="fw-semibold">{{ optional($document->period)->label ?: '-' }}</div></div>
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.views.shared.status') }}</div><div class="fw-semibold">{{ $document->status ?: '-' }}</div></div>
                        <div class="col-6"><div class="text-muted fs-8">{{ __('vasaccounting::lang.views.shared.date') }}</div><div class="fw-semibold">{{ optional($document->posted_at)->format('Y-m-d H:i') ?: '-' }}</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.movement.title') }}</div></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>#</th><th>{{ __('vasaccounting::lang.views.shared.product') }}</th><th>{{ __('vasaccounting::lang.views.inventory.movement.qty') }}</th><th>{{ __('vasaccounting::lang.views.inventory.document_form.unit_cost') }}</th><th>{{ __('vasaccounting::lang.views.inventory.movement.value') }}</th><th>{{ __('vasaccounting::lang.views.shared.type') }}</th></tr></thead>
                    <tbody>
                        @forelse($document->lines as $line)
                            <tr>
                                <td>{{ $line->line_no }}</td>
                                <td>{{ optional($line->product)->name ?: ('#' . $line->product_id) }}</td>
                                <td>{{ number_format((float) $line->quantity, 4) }}</td>
                                <td>{{ number_format((float) $line->unit_cost, 4) }}</td>
                                <td>{{ number_format((float) $line->amount, 4) }}</td>
                                <td>{{ ucfirst((string) ($line->direction ?: '-')) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ __('vasaccounting::lang.views.inventory.movement.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush mt-8">
        <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.document_links.title') }}</div></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.shared.document') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.type') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.source') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.date') }}</th>
                            <th class="text-end">{{ __('vasaccounting::lang.views.shared.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($storageDocumentLinks as $link)
                            @php
                                $storageDocument = $link->document;
                            @endphp
                            <tr>
                                <td class="fw-semibold text-gray-900">{{ optional($storageDocument)->document_no ?: ('#' . $link->document_id) }}</td>
                                <td>{{ $storageDocument ? ucwords(str_replace('_', ' ', (string) $storageDocument->document_type)) : '-' }}</td>
                                <td>{{ $storageDocument?->status ?: '-' }}</td>
                                <td>{{ $link->linked_ref ?: ('#' . (int) $link->linked_id) }}</td>
                                <td>{{ optional($link->synced_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td class="text-end">
                                    @if($canOpenStorageDocument && !empty($link->document_id))
                                        @if($storageDocument && $storageDocument->document_type === 'receipt' && $storageDocument->source_type && $storageDocument->source_id && \Illuminate\Support\Facades\Route::has('storage-manager.inbound.show'))
                                            <a href="{{ route('storage-manager.inbound.show', ['sourceType' => $storageDocument->source_type, 'sourceId' => $storageDocument->source_id]) }}"
                                               class="btn btn-sm btn-light-primary">
                                                @lang('lang_v1.inbound_receipt')
                                            </a>
                                        @else
                                            <a href="#"
                                                class="btn-modal btn btn-sm btn-light-primary"
                                                data-container=".view_modal"
                                                data-href="{{ route('storage-manager.documents.show', $link->document_id) }}">
                                                {{ __('vasaccounting::lang.views.inventory.document_links.open_storage_document') }}
                                            </a>
                                        @endif
                                    @else
                                        <span class="text-muted fs-8">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ __('vasaccounting::lang.views.inventory.document_links.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush mt-8">
        <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.sync_timeline.title') }}</div></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.shared.document') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.source') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.action') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                            <th>{{ __('vasaccounting::lang.views.inventory.sync_timeline.actor') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.description') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($storageSyncLogs as $syncLog)
                            <tr>
                                <td>
                                    @if($canOpenStorageDocument && !empty($syncLog->document_id))
                                        <a href="#"
                                            class="btn-modal text-primary"
                                            data-container=".view_modal"
                                            data-href="{{ route('storage-manager.documents.show', $syncLog->document_id) }}">
                                            {{ optional($syncLog->document)->document_no ?: ('#' . $syncLog->document_id) }}
                                        </a>
                                    @else
                                        {{ optional($syncLog->document)->document_no ?: (!empty($syncLog->document_id) ? ('#' . $syncLog->document_id) : '-') }}
                                    @endif
                                </td>
                                <td>{{ strtoupper((string) $syncLog->linked_system) }}</td>
                                <td>{{ $syncLog->action ?: '-' }}</td>
                                <td>{{ $syncLog->status ?: '-' }}</td>
                                <td>{{ $syncLog->actor_label }}</td>
                                <td>{{ $syncLog->message ?: '-' }}</td>
                                <td>{{ optional($syncLog->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.inventory.sync_timeline.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
