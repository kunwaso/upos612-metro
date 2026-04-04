@extends('layouts.app')

@section('title', 'Goods Received Note')

@section('content')
<style>
    .grn-sheet {
        max-width: 1080px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #d9d9e3;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
    }

    .grn-header-line {
        height: 4px;
        background: #1f4ea3;
    }

    .grn-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #111827;
        margin-bottom: 0.75rem;
    }

    .grn-rule {
        border-top: 2px solid #1f2937;
        margin: 1.75rem 0;
    }

    .grn-table thead th {
        background: #1f4ea3;
        color: #fff;
        border-color: #1f4ea3;
        font-size: 0.85rem;
        text-transform: uppercase;
    }

    .grn-comments {
        min-height: 120px;
        white-space: pre-wrap;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .grn-sheet {
            border: 0;
            box-shadow: none;
        }

        .app-toolbar,
        .app-sidebar,
        .app-header,
        .app-footer {
            display: none !important;
        }

        .app-content,
        .app-container,
        .container-xxl {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }
    }
</style>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 no-print">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Goods Received Note
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">@lang('lang_v1.storage_manager')</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Inbound Receiving</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">Goods Received Note</li>
                </ul>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('storage-manager.inbound.show', ['sourceType' => $document->source_type, 'sourceId' => $document->source_id]) }}" class="btn btn-sm btn-light">
                    @lang('messages.back')
                </a>
                <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
                    @lang('messages.print')
                </button>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="grn-sheet">
                <div class="grn-header-line"></div>
                <div class="p-10 p-lg-15">
                    <div class="text-center mb-10">
                        <h1 class="fs-1 fw-bolder text-gray-900 mb-4">GOODS RECEIVED NOTE</h1>
                        <div class="grn-rule"></div>
                    </div>

                    <div class="row g-6 mb-8">
                        <div class="col-md-6">
                            <div class="fs-5 fw-bold text-gray-900">GRN NUMBER: <span class="fw-normal">{{ $grn['grn_number'] ?? $document->document_no }}</span></div>
                            <div class="fs-5 fw-bold text-gray-900">DATE: <span class="fw-normal">{{ $grn['grn_date'] ?? optional($document->completed_at)->toDateString() }}</span></div>
                        </div>
                    </div>

                    <div class="row g-8 mb-10">
                        <div class="col-md-6">
                            <div class="grn-section-title">Delivery Information</div>
                            <div class="fs-5 text-gray-900"><strong>Delivery Note Number:</strong> {{ $grn['delivery_note_number'] ?: '—' }}</div>
                            <div class="fs-5 text-gray-900"><strong>Delivery Date:</strong> {{ $grn['delivery_date'] ?: '—' }}</div>
                            <div class="fs-5 text-gray-900"><strong>Carrier / Driver Name:</strong> {{ $grn['carrier_driver_name'] ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="grn-section-title">Supplier Information</div>
                            <div class="fs-5 text-gray-900"><strong>Supplier Name:</strong> {{ $supplierName }}</div>
                            <div class="fs-5 text-gray-900"><strong>Supplier Address:</strong> {{ $supplierAddress }}</div>
                            <div class="fs-5 text-gray-900"><strong>Supplier Contact Information:</strong> {{ $supplierContact }}</div>
                        </div>
                    </div>

                    <div class="row g-8 mb-10">
                        <div class="col-md-6">
                            <div class="grn-section-title">Received By</div>
                            <div class="fs-5 text-gray-900"><strong>Name:</strong> {{ $grn['received_by_name'] ?: '—' }}</div>
                            <div class="fs-5 text-gray-900"><strong>Receiving Department:</strong> {{ $grn['receiving_department'] ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="grn-section-title">Source Purchase</div>
                            <div class="fs-5 text-gray-900"><strong>Purchase Ref:</strong> {{ $sourceDocument->ref_no ?: $sourceDocument->invoice_no ?: ('PUR-' . $sourceDocument->id) }}</div>
                            <div class="fs-5 text-gray-900"><strong>Supplier:</strong> {{ $supplierName }}</div>
                            <div class="fs-5 text-gray-900"><strong>Location:</strong> {{ optional($sourceDocument->location)->name ?: '—' }}</div>
                        </div>
                    </div>

                    <div class="table-responsive mb-8">
                        <table class="table table-bordered align-middle grn-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>Unit Of Measure</th>
                                    <th>Quantity Ordered</th>
                                    <th>Quantity Received</th>
                                    <th>Unit Price</th>
                                    <th>Total Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>{{ $item['item'] }}</td>
                                        <td>{{ $item['description'] ?: '—' }}</td>
                                        <td>{{ $item['unit_of_measure'] ?: '—' }}</td>
                                        <td>{{ format_quantity_value($item['quantity_ordered']) }}</td>
                                        <td>{{ format_quantity_value($item['quantity_received']) }}</td>
                                        <td>@format_currency($item['unit_price'])</td>
                                        <td>@format_currency($item['total_price'])</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-8">No received items.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mb-10">
                        <table class="table table-bordered w-auto">
                            <tbody>
                                <tr>
                                    <th class="bg-light w-200px">TOTAL ITEMS</th>
                                    <td>{{ format_quantity_value($totalItems) }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">TOTAL AMOUNT</th>
                                    <td>@format_currency($totalAmount)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-10">
                        <div class="grn-section-title">Received Condition</div>
                        <div class="border border-gray-300 rounded p-4 fs-5 text-gray-900">{{ $grn['received_condition'] ?: '—' }}</div>
                    </div>

                    <div>
                        <div class="grn-section-title">Comments</div>
                        <div class="border border-gray-300 rounded p-4 fs-5 text-gray-900 grn-comments">{{ $grn['comments'] ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
    @if(request()->boolean('print'))
        window.addEventListener('load', function () {
            window.print();
        });
    @endif
</script>
@endsection
