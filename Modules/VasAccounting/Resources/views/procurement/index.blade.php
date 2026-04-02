@extends('layouts.app')

@section('title', 'Procurement Workspace')

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => 'Procurement Workspace',
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    @if ($closePeriod)
        <div class="alert alert-warning d-flex flex-column flex-sm-row align-items-start align-items-sm-center mb-8">
            <div class="me-4">
                <div class="fw-bold text-gray-900">Close-scope procurement review for {{ $vasAccountingUtil->localizedPeriodName($closePeriod->name) }}</div>
                <div class="text-muted fs-7">Documents are filtered to {{ optional($closePeriod->start_date)->format('Y-m-d') }} through {{ optional($closePeriod->end_date)->format('Y-m-d') }}.</div>
            </div>
        </div>
    @endif

    @if ($workspaceFocus)
        <div class="card border border-warning mb-8">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
                <div>
                    <div class="fw-bold text-gray-900">Focused procurement review: {{ str($workspaceFocus)->replace('_', ' ')->title() }}</div>
                    <div class="text-muted fs-7">
                        @if ($workspaceFocus === 'pending_documents')
                            Draft, submitted, and approval-ready procurement documents.
                        @elseif ($workspaceFocus === 'receiving_queue')
                            Purchase orders waiting to be ordered, partially received, or fully received.
                        @elseif ($workspaceFocus === 'discrepancy_queue')
                            Match discrepancies grouped from the latest supplier invoice match runs.
                        @else
                            Supplier invoices still waiting for clean matching results before posting.
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="#procurement-register" class="btn btn-light btn-sm">Jump to register</a>
                    <a href="{{ route('vasaccounting.procurement.index', array_filter(['location_id' => $selectedLocationId, 'period_id' => $closePeriod?->id])) }}" class="btn btn-light-danger btn-sm">Clear focus</a>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Documents</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['documents']) }}</div>
                    <div class="text-muted fs-8 mt-1">Canonical procurement and AP-source documents in scope.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Pending workflow</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['pending_documents']) }}</div>
                    <div class="text-muted fs-8 mt-1">Draft, submitted, or approved documents not yet operationally complete.</div>
                    <a href="{{ route('vasaccounting.procurement.index', array_filter(['location_id' => $selectedLocationId, 'period_id' => $closePeriod?->id, 'focus' => 'pending_documents'])) }}" class="btn btn-light-primary btn-sm mt-4">Review queue</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 border border-warning">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Receiving queue</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['receiving_queue']) }}</div>
                    <div class="text-muted fs-8 mt-1">Purchase orders that still need ordering or receipt progression.</div>
                    <a href="{{ route('vasaccounting.procurement.index', array_filter(['location_id' => $selectedLocationId, 'period_id' => $closePeriod?->id, 'focus' => 'receiving_queue'])) }}" class="btn btn-light-warning btn-sm mt-4">Review receiving</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 border border-danger">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Pending matching</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['pending_matching']) }}</div>
                    <div class="text-muted fs-8 mt-1">Supplier invoices that still need matching attention.</div>
                    <a href="{{ route('vasaccounting.procurement.index', array_filter(['location_id' => $selectedLocationId, 'period_id' => $closePeriod?->id, 'focus' => 'pending_matching'])) }}" class="btn btn-light-danger btn-sm mt-4">Review matching</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 border border-danger">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Open discrepancies</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['open_discrepancies']) }}</div>
                    <div class="text-muted fs-8 mt-1">Latest supplier-invoice match exceptions that still need operational follow-up.</div>
                    <a href="{{ route('vasaccounting.procurement.index', array_filter(['location_id' => $selectedLocationId, 'period_id' => $closePeriod?->id, 'focus' => 'discrepancy_queue'])) }}" class="btn btn-light-danger btn-sm mt-4">Review discrepancies</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Posted documents</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['posted_documents']) }}</div>
                    <div class="text-muted fs-8 mt-1">GRNs and supplier invoices already posted into the ledger.</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">Gross amount</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $summary['gross_amount'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ $currency }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8" id="procurement-discrepancy-queue">
        <div class="col-12">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Procurement discrepancy queue</span>
                        <span class="text-muted fs-7">Work the latest supplier-invoice match exceptions from one queue instead of opening each invoice individually.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-5 mb-6">
                        <div class="col-md-3">
                            <div class="border border-gray-300 rounded p-4 h-100">
                                <div class="text-muted fw-semibold fs-7">Open discrepancies</div>
                                <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $discrepancySummary['total']) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border border-danger rounded p-4 h-100">
                                <div class="text-muted fw-semibold fs-7">Blocking</div>
                                <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $discrepancySummary['blocking']) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border border-warning rounded p-4 h-100">
                                <div class="text-muted fw-semibold fs-7">Warnings</div>
                                <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $discrepancySummary['warning']) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border border-gray-300 rounded p-4 h-100">
                                <div class="text-muted fw-semibold fs-7">Supplier invoices</div>
                                <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $discrepancySummary['documents']) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Invoice</th>
                                    <th>Severity</th>
                                    <th>Code</th>
                                    <th>Message</th>
                                    <th>Line</th>
                                    <th>Match summary</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($discrepancyQueue as $item)
                                    <tr>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ $item['document_no'] }}</div>
                                            <div class="text-muted fs-8">{{ $item['document_date'] }} | {{ $vasAccountingUtil->genericStatusLabel($item['workflow_status']) }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $item['severity'] === 'blocking' ? 'badge-light-danger' : 'badge-light-warning' }}">{{ strtoupper($item['severity']) }}</span>
                                        </td>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ str($item['code'])->replace('_', ' ')->title() }}</div>
                                            <div class="text-muted fs-8">{{ $item['code'] }}</div>
                                        </td>
                                        <td>
                                            <div class="text-gray-900">{{ $item['message'] }}</div>
                                            @if (! empty($item['meta']['match_key']))
                                                <div class="text-muted fs-8 mt-1">Match key: {{ $item['meta']['match_key'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ $item['line_no'] > 0 ? 'Line #' . $item['line_no'] : 'Header' }}</div>
                                            <div class="text-muted fs-8">{{ $item['product_id'] > 0 ? 'Product #' . $item['product_id'] : 'No product key' }}</div>
                                        </td>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ str($item['match_status'])->replace('_', ' ')->title() }}</div>
                                            <div class="text-muted fs-8">Blocking {{ $item['blocking_exception_count'] }} | Warning {{ $item['warning_count'] }}</div>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('vasaccounting.procurement.match', $item['document_id']) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-light-warning btn-sm w-100">Re-run match</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-10">No open procurement discrepancies are currently queued.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        @foreach ($supportedDocumentTypes as $type)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <span class="text-muted fw-semibold fs-7">{{ $vasAccountingUtil->documentTypeLabel($type) }}</span>
                        <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) ($documentTypeCounts[$type] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100" id="procurement-register">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Procurement register</span>
                        <span class="text-muted fs-7">Create native P2P documents on the finance-core workflow.</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.procurement.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Document type</label>
                            <select name="document_type" class="form-select form-select-solid" required>
                                @foreach ($supportedDocumentTypes as $type)
                                    <option value="{{ $type }}">{{ $vasAccountingUtil->documentTypeLabel($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Document no.</label>
                            <input type="text" name="document_no" class="form-control form-control-solid" placeholder="P2P-2026-001" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">External reference</label>
                            <input type="text" name="external_reference" class="form-control form-control-solid" placeholder="PO-REF-001">
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Linked parent document</label>
                            <select name="parent_document_id" class="form-select form-select-solid" data-control="select2">
                                <option value="">No parent link</option>
                                @foreach ($parentDocumentOptions as $parentDocument)
                                    <option value="{{ $parentDocument->id }}">
                                        {{ $parentDocument->document_no }} | {{ $vasAccountingUtil->documentTypeLabel($parentDocument->document_type) }} | {{ $vasAccountingUtil->genericStatusLabel($parentDocument->workflow_status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Supplier</label>
                            <select name="counterparty_id" class="form-select form-select-solid" data-control="select2">
                                @foreach ($supplierOptions as $supplierId => $supplierLabel)
                                    <option value="{{ $supplierId }}">{{ $supplierLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.branch') }}</label>
                                <select name="business_location_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.product') }}</label>
                                <select name="product_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_product') }}</option>
                                    @foreach ($productOptions as $productId => $productLabel)
                                        <option value="{{ $productId }}">{{ $productLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">Document date</label>
                                <input type="date" name="document_date" class="form-control form-control-solid" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Posting date</label>
                                <input type="date" name="posting_date" class="form-control form-control-solid" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.shared.description') }}</label>
                            <input type="text" name="description" class="form-control form-control-solid" placeholder="Describe the requisition, order, receipt, or invoice line" required>
                        </div>
                        <div class="row g-5 mb-5">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.quantity') }}</label>
                                <input type="number" name="quantity" step="0.0001" min="0.0001" class="form-control form-control-solid" value="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unit price</label>
                                <input type="number" name="unit_price" step="0.0001" min="0" class="form-control form-control-solid" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tax amount</label>
                                <input type="number" name="tax_amount" step="0.0001" min="0" class="form-control form-control-solid" value="0">
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Debit account</label>
                            <select name="debit_account_id" class="form-select form-select-solid" data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Credit account</label>
                            <select name="credit_account_id" class="form-select form-select-solid" data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <label class="form-label">Tax account</label>
                                <select name="tax_account_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                    @foreach ($chartOptions as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tax code</label>
                                <select name="tax_code_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">No tax code</option>
                                    @foreach ($taxCodeOptions as $taxCode)
                                        <option value="{{ $taxCode->id }}">{{ $taxCode->code }} - {{ $taxCode->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save procurement document</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">Procurement document workspace</span>
                        <span class="text-muted fs-7">Track requisitions, orders, receipts, and supplier invoices in one queue.</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.document') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.counterparty') }}</th>
                                    <th>Chain</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.amount') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($procurementDocuments as $document)
                                    @php($parentDocument = optional($document->parentLinks->first())->parentDocument)
                                    @php($childDocuments = $document->childLinks->pluck('childDocument')->filter())
                                    @php($approvalInstance = $document->approvalInstances->first())
                                    @php($latestMatchStatus = data_get($document->meta, 'matching.latest_status'))
                                    @php($latestMatchWarnings = (int) data_get($document->meta, 'matching.warning_count', 0))
                                    @php($latestMatchBlocks = (int) data_get($document->meta, 'matching.blocking_exception_count', 0))
                                    <tr>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ $document->document_no }}</div>
                                            <div class="text-muted fs-8">
                                                {{ optional($document->document_date)->format('Y-m-d') }}
                                                |
                                                {{ $vasAccountingUtil->documentTypeLabel($document->document_type) }}
                                                @if ($document->external_reference)
                                                    | {{ $document->external_reference }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">
                                                {{ $supplierOptions[$document->counterparty_id] ?? __('vasaccounting::lang.views.shared.no') }}
                                            </div>
                                            <div class="text-muted fs-8">
                                                {{ $locationOptions[$document->business_location_id] ?? __('vasaccounting::lang.views.shared.select_branch') }}
                                            </div>
                                        </td>
                                        <td>
                                            @if ($parentDocument)
                                                <div class="text-gray-900 fw-semibold">Parent: {{ $parentDocument->document_no }}</div>
                                                <div class="text-muted fs-8">{{ $vasAccountingUtil->documentTypeLabel($parentDocument->document_type) }}</div>
                                            @else
                                                <div class="text-muted fs-8">No parent link</div>
                                            @endif
                                            <div class="text-muted fs-8 mt-1">Child documents: {{ $childDocuments->count() }}</div>
                                            @if ($document->document_type === 'supplier_invoice' && $latestMatchStatus)
                                                <div class="text-muted fs-8 mt-1">
                                                    Match: {{ str($latestMatchStatus)->replace('_', ' ')->title() }}
                                                    @if ($latestMatchWarnings > 0)
                                                        | warnings {{ $latestMatchWarnings }}
                                                    @endif
                                                    @if ($latestMatchBlocks > 0)
                                                        | blocking {{ $latestMatchBlocks }}
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ number_format((float) $document->gross_amount, 2) }} {{ $currency }}</div>
                                            <div class="text-muted fs-8">
                                                Net {{ number_format((float) $document->net_amount, 2) }}
                                                @if ((float) $document->tax_amount > 0)
                                                    | Tax {{ number_format((float) $document->tax_amount, 2) }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge badge-light-primary">{{ $vasAccountingUtil->genericStatusLabel((string) $document->workflow_status) }}</span>
                                                <span class="badge badge-light-secondary">{{ $vasAccountingUtil->genericStatusLabel((string) $document->accounting_status) }}</span>
                                                @if ($approvalInstance)
                                                    <span class="text-muted fs-8">Approval step {{ (int) ($approvalInstance->current_step_no ?: 1) }}/{{ max(1, $approvalInstance->steps->count()) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                @if (in_array($document->workflow_status, ['draft', 'rejected'], true))
                                                    <form method="POST" action="{{ route('vasaccounting.procurement.submit', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-primary btn-sm w-100">Submit</button>
                                                    </form>
                                                @endif

                                                @if ($document->workflow_status === 'submitted')
                                                    <form method="POST" action="{{ route('vasaccounting.procurement.approve', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-success btn-sm w-100">Approve</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('vasaccounting.procurement.reject', $document->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="Sent back from procurement workspace">
                                                        <button type="submit" class="btn btn-light-danger btn-sm w-100">Reject</button>
                                                    </form>
                                                @endif

                                                @if ($document->document_type === 'purchase_requisition' && $document->workflow_status === 'approved')
                                                    <form method="POST" action="{{ route('vasaccounting.procurement.fulfill', $document->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="completion_state" value="converted_to_po">
                                                        <button type="submit" class="btn btn-light-warning btn-sm w-100">Mark converted to PO</button>
                                                    </form>
                                                @endif

                                                @if ($document->document_type === 'purchase_order' && in_array($document->workflow_status, ['approved', 'ordered', 'partially_received'], true))
                                                    @foreach (['ordered' => 'Mark ordered', 'partially_received' => 'Mark partial receipt', 'fully_received' => 'Mark fully received'] as $state => $label)
                                                        <form method="POST" action="{{ route('vasaccounting.procurement.fulfill', $document->id) }}">
                                                            @csrf
                                                            <input type="hidden" name="completion_state" value="{{ $state }}">
                                                            <button type="submit" class="btn btn-light-warning btn-sm w-100">{{ $label }}</button>
                                                        </form>
                                                    @endforeach
                                                @endif

                                                @if ($document->document_type === 'supplier_invoice' && $document->workflow_status === 'approved')
                                                    <form method="POST" action="{{ route('vasaccounting.procurement.match', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-warning btn-sm w-100">Run match</button>
                                                    </form>
                                                @endif

                                                @if (
                                                    ($document->document_type === 'goods_receipt' && $document->workflow_status === 'approved')
                                                    || ($document->document_type === 'supplier_invoice' && in_array($document->workflow_status, ['approved', 'matched'], true))
                                                )
                                                    <form method="POST" action="{{ route('vasaccounting.procurement.post', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-success btn-sm w-100">Post</button>
                                                    </form>
                                                @endif

                                                @if (
                                                    ($document->document_type === 'purchase_requisition' && in_array($document->workflow_status, ['approved', 'converted_to_po'], true))
                                                    || ($document->document_type === 'purchase_order' && in_array($document->workflow_status, ['approved', 'ordered', 'partially_received', 'fully_received'], true))
                                                    || ($document->document_type === 'supplier_invoice' && $document->workflow_status === 'posted')
                                                )
                                                    <form method="POST" action="{{ route('vasaccounting.procurement.close', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-dark btn-sm w-100">Close</button>
                                                    </form>
                                                @endif

                                                @if (in_array($document->workflow_status, ['posted'], true) && in_array($document->document_type, ['goods_receipt', 'supplier_invoice'], true))
                                                    <form method="POST" action="{{ route('vasaccounting.procurement.reverse', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-danger btn-sm w-100">Reverse</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-10">No procurement documents matched the current scope.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
