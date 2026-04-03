@extends('layouts.app')

@section('title', __('vasaccounting::lang.inventory'))

@section('content')
    @php
        $currency = config('vasaccounting.book_currency', 'VND');
        $selectedLocationLabel = !empty($selectedLocationId) ? ($locationOptions[$selectedLocationId] ?? null) : null;
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.inventory'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    @if (session('status.msg'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="fas fa-check-circle mt-1"></i>
            <div>{{ session('status.msg') }}</div>
        </div>
    @endif

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-12">
            <div class="card card-flush">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 py-5">
                    <div>
                        <div class="text-gray-900 fw-bold fs-5">{{ __('vasaccounting::lang.views.inventory.scope.title') }}</div>
                        <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.inventory.scope.subtitle') }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-primary">{{ __('vasaccounting::lang.views.inventory.scope.workspace') }}</span>
                        <span class="badge badge-light-info">{{ $selectedLocationLabel ? __('vasaccounting::lang.views.cash_bank.scope.location', ['location' => $selectedLocationLabel]) : __('vasaccounting::lang.ui.all_locations') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fs-7 fw-semibold">{{ __('vasaccounting::lang.views.inventory.cards.skus') }}</div><div class="text-gray-900 fw-bold fs-1">{{ $totals['sku_count'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fs-7 fw-semibold">{{ __('vasaccounting::lang.views.inventory.cards.quantity_on_hand') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format($totals['quantity_on_hand'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fs-7 fw-semibold">{{ __('vasaccounting::lang.views.inventory.cards.inventory_value') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format($totals['inventory_value'], 2) }}</div><div class="text-muted fs-8">{{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fs-7 fw-semibold">{{ __('vasaccounting::lang.views.inventory.cards.active_warehouses') }}</div><div class="text-gray-900 fw-bold fs-1">{{ $warehouseSummary['active_warehouses'] }}</div><div class="text-muted fs-8 mt-2">{{ __('vasaccounting::lang.views.inventory.cards.uncovered_locations', ['count' => $warehouseSummary['uncovered_locations']]) }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.warehouse_form.title') }}</div></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.inventory.warehouses.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.inventory.warehouse_form.warehouse_code') }}</label>
                            <input type="text" name="code" class="form-control form-control-solid" placeholder="WH-HQ" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.inventory.warehouse_form.warehouse_name') }}</label>
                            <input type="text" name="name" class="form-control form-control-solid" placeholder="{{ __('vasaccounting::lang.views.inventory.warehouse_form.placeholder') }}" required>
                        </div>
                        <div class="mb-6">
                            <label class="form-label">{{ __('vasaccounting::lang.views.shared.branch') }}</label>
                            <select name="business_location_id" class="form-select form-select-solid">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                @foreach ($locationOptions as $locationId => $locationLabel)
                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.inventory.warehouse_form.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.coverage.title') }}</div></div>
                <div class="card-body">
                    <div class="row g-5 mb-6">
                        <div class="col-md-4"><div class="border border-gray-200 rounded p-5 h-100"><div class="text-muted fs-8 mb-1">{{ __('vasaccounting::lang.views.inventory.coverage.warehouse_masters') }}</div><div class="fw-bold fs-2">{{ $warehouseSummary['warehouse_count'] }}</div></div></div>
                        <div class="col-md-4"><div class="border border-gray-200 rounded p-5 h-100"><div class="text-muted fs-8 mb-1">{{ __('vasaccounting::lang.views.inventory.coverage.stock_locations') }}</div><div class="fw-bold fs-2">{{ $warehouseSummary['stock_locations'] }}</div></div></div>
                        <div class="col-md-4"><div class="border border-gray-200 rounded p-5 h-100"><div class="text-muted fs-8 mb-1">{{ __('vasaccounting::lang.views.inventory.coverage.discrepancies') }}</div><div class="fw-bold fs-2">{{ $warehouseSummary['warehouse_discrepancies'] }}</div></div></div>
                    </div>

                    @forelse ($warehouses as $warehouse)
                        <div class="border border-gray-200 rounded p-4 mb-3">
                            <div class="fw-bold text-gray-900">{{ $warehouse->code }} - {{ $warehouse->name }}</div>
                            <div class="text-muted fs-8">{{ optional($warehouse->businessLocation)->name ?: __('vasaccounting::lang.views.inventory.coverage.no_branch') }} | {{ $vasAccountingUtil->genericStatusLabel((string) $warehouse->status) }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ __('vasaccounting::lang.views.inventory.coverage.empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.document_form.title') }}</div></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.inventory.documents.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.document_type') }}</label>
                                <select name="document_type" class="form-select form-select-solid" required>
                                    <option value="receipt">{{ __('vasaccounting::lang.document_types.inventory_receipt') }}</option>
                                    <option value="issue">{{ __('vasaccounting::lang.document_types.inventory_issue') }}</option>
                                    <option value="transfer">{{ __('vasaccounting::lang.document_types.inventory_transfer') }}</option>
                                    <option value="adjustment">{{ __('vasaccounting::lang.document_types.inventory_adjustment') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.status') }}</label>
                                <select name="status" class="form-select form-select-solid">
                                    <option value="approved">{{ __('vasaccounting::lang.document_statuses.approved') }}</option>
                                    <option value="draft">{{ __('vasaccounting::lang.document_statuses.draft') }}</option>
                                    <option value="pending_approval">{{ __('vasaccounting::lang.document_statuses.pending_approval') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.document_date') }}</label>
                                <input type="date" name="document_date" class="form-control form-control-solid" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.posting_date') }}</label>
                                <input type="date" name="posting_date" class="form-control form-control-solid" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.branch') }}</label>
                                <select name="business_location_id" class="form-select form-select-solid">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.warehouse') }}</label>
                                <select name="warehouse_id" class="form-select form-select-solid" required>
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_warehouse') }}</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->code }} - {{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.destination_warehouse') }}</label>
                                <select name="destination_warehouse_id" class="form-select form-select-solid">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_destination') }}</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->code }} - {{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.offset_account') }}</label>
                                <select name="offset_account_id" class="form-select form-select-solid">
                                    <option value="">{{ __('vasaccounting::lang.views.inventory.document_form.offset_account_default') }}</option>
                                    @foreach ($offsetAccountOptions as $accountId => $accountLabel)
                                        <option value="{{ $accountId }}">{{ $accountLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.product') }}</label>
                                <select name="lines[0][product_id]" class="form-select form-select-solid" required>
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_product') }}</option>
                                    @foreach ($productOptions as $productId => $productLabel)
                                        <option value="{{ $productId }}">{{ $productLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.quantity') }}</label>
                                <input type="number" step="0.0001" min="0.0001" name="lines[0][quantity]" class="form-control form-control-solid" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.unit_cost') }}</label>
                                <input type="number" step="0.0001" min="0" name="lines[0][unit_cost]" class="form-control form-control-solid" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.inventory.document_form.adjustment_direction') }}</label>
                                <select name="lines[0][direction]" class="form-select form-select-solid">
                                    <option value="">{{ __('vasaccounting::lang.views.inventory.document_form.auto_by_document') }}</option>
                                    <option value="increase">{{ __('vasaccounting::lang.views.inventory.document_form.increase') }}</option>
                                    <option value="decrease">{{ __('vasaccounting::lang.views.inventory.document_form.decrease') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.description') }}</label>
                                <textarea name="description" class="form-control form-control-solid" rows="3" placeholder="{{ __('vasaccounting::lang.views.inventory.document_form.description_placeholder') }}"></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.inventory.document_form.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.recent_documents.title') }}</div></div>
                <div class="card-body">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-inventory-documents-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-inventory-documents-table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.document') }}</th><th>{{ __('vasaccounting::lang.views.shared.type') }}</th><th>{{ __('vasaccounting::lang.views.inventory.document_form.warehouse') }}</th><th>{{ __('vasaccounting::lang.views.shared.status') }}</th><th>{{ __('vasaccounting::lang.views.shared.voucher') }}</th><th class="text-end">{{ __('vasaccounting::lang.views.shared.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inventoryDocuments as $document)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900">
                                                <a href="{{ route('vasaccounting.inventory.documents.show', $document->id) }}">{{ $document->document_no }}</a>
                                            </div>
                                            <div class="text-muted fs-8">{{ optional($document->document_date)->format('Y-m-d') }}</div>
                                        </td>
                                        <td>{{ $vasAccountingUtil->documentTypeLabel((string) $document->document_type) }}</td>
                                        <td>
                                            {{ optional($document->warehouse)->code ?: '-' }}
                                            @if ($document->destinationWarehouse)
                                                <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.inventory.recent_documents.to_destination', ['code' => $document->destinationWarehouse->code]) }}</div>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $document->status === 'posted' ? 'badge-light-success' : ($document->status === 'reversed' ? 'badge-light-danger' : 'badge-light-warning') }}">{{ $vasAccountingUtil->documentStatusLabel((string) $document->status) }}</span></td>
                                        <td>{{ optional($document->postedVoucher)->voucher_no ?: '-' }}</td>
                                        <td class="text-end">
                                            @if (in_array($document->status, ['draft', 'pending_approval', 'approved'], true))
                                                <form method="POST" action="{{ route('vasaccounting.inventory.documents.post', $document->id) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-light-primary">{{ $vasAccountingUtil->actionLabel('post') }}</button></form>
                                            @elseif ($document->status === 'posted' && optional($document->postedVoucher)->status === 'posted')
                                                <form method="POST" action="{{ route('vasaccounting.inventory.documents.reverse', $document->id) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-light-danger">{{ $vasAccountingUtil->actionLabel('reverse') }}</button></form>
                                            @elseif ($document->status === 'posted')
                                                <span class="text-warning fs-8">{{ __('vasaccounting::lang.inventory_reverse_requires_posted_voucher') }}</span>
                                            @else
                                                <span class="text-muted fs-8">{{ __('vasaccounting::lang.views.inventory.recent_documents.no_action') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted">{{ __('vasaccounting::lang.views.inventory.recent_documents.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.movement.title') }}</div></div>
        <div class="card-body">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-inventory-movements-search',
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-inventory-movements-table">
                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('vasaccounting::lang.views.shared.date') }}</th><th>{{ __('vasaccounting::lang.views.shared.reference') }}</th><th>{{ __('vasaccounting::lang.views.shared.type') }}</th><th>{{ __('vasaccounting::lang.views.inventory.movement.product') }}</th><th>{{ __('vasaccounting::lang.views.inventory.movement.warehouse_branch') }}</th><th>{{ __('vasaccounting::lang.views.inventory.movement.qty') }}</th><th>{{ __('vasaccounting::lang.views.inventory.movement.value') }}</th></tr></thead>
                    <tbody>
                        @forelse ($movementRows as $row)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($row->transaction_date)->format('Y-m-d') }}</td>
                                <td class="text-gray-900 fw-semibold">{{ $row->reference }}</td>
                                <td><span class="badge {{ $row->direction === 'in' ? 'badge-light-success' : 'badge-light-danger' }}">{{ $vasAccountingUtil->documentTypeLabel((string) $row->movement_type) }}</span></td>
                                <td>{{ $row->sku }} - {{ $row->product_name }}</td>
                                <td>{{ $row->location_name }}</td>
                                <td>{{ number_format((float) $row->quantity, 2) }}</td>
                                <td>{{ number_format((float) $row->movement_value, 2) }} {{ $currency }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.inventory.movement.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.reconciliation.title') }}</div></div>
        <div class="card-body">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-inventory-reconciliation-search',
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-inventory-reconciliation-table">
                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('vasaccounting::lang.views.shared.branch') }}</th><th>{{ __('vasaccounting::lang.views.inventory.reconciliation.warehouse_master') }}</th><th>{{ __('vasaccounting::lang.views.inventory.reconciliation.skus') }}</th><th>{{ __('vasaccounting::lang.views.inventory.reconciliation.qty_on_hand') }}</th><th>{{ __('vasaccounting::lang.views.inventory.cards.inventory_value') }}</th><th>{{ __('vasaccounting::lang.views.inventory.reconciliation.last_movement') }}</th><th>{{ __('vasaccounting::lang.views.shared.status') }}</th></tr></thead>
                    <tbody>
                        @forelse ($reconciliationRows as $row)
                            <tr>
                                <td>{{ $row['location_name'] }}</td>
                                <td>{{ $row['warehouse_code'] ? ($row['warehouse_code'] . ' - ' . $row['warehouse_name']) : __('vasaccounting::lang.views.inventory.reconciliation.missing_warehouse_master') }}</td>
                                <td>{{ $row['sku_count'] }}</td>
                                <td>{{ number_format($row['qty_available'], 2) }}</td>
                                <td>{{ number_format($row['inventory_value'], 2) }} {{ $currency }}</td>
                                <td>{{ $row['last_movement_at'] ? \Illuminate\Support\Carbon::parse($row['last_movement_at'])->format('Y-m-d') : '-' }}</td>
                                <td><span class="badge {{ $row['coverage_status'] === 'aligned' ? 'badge-light-success' : ($row['coverage_status'] === 'missing_master' ? 'badge-light-danger' : 'badge-light-warning') }}">{{ $vasAccountingUtil->coverageStatusLabel((string) $row['coverage_status']) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.inventory.reconciliation.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.inventory.valuation.title') }}</div></div>
        <div class="card-body">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-inventory-valuation-search',
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-inventory-valuation-table">
                    <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('vasaccounting::lang.views.inventory.valuation.sku') }}</th><th>{{ __('vasaccounting::lang.views.inventory.movement.product') }}</th><th>{{ __('vasaccounting::lang.views.shared.branch') }}</th><th>{{ __('vasaccounting::lang.views.inventory.valuation.qty_available') }}</th><th>{{ __('vasaccounting::lang.views.inventory.valuation.average_cost') }}</th><th>{{ __('vasaccounting::lang.views.inventory.cards.inventory_value') }}</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['sku'] }}</td>
                                <td>{{ $row['product_name'] }}</td>
                                <td>{{ $row['location_name'] ?: __('vasaccounting::lang.views.inventory.valuation.location_fallback', ['id' => $row['location_id']]) }}</td>
                                <td>{{ number_format($row['qty_available'], 2) }}</td>
                                <td>{{ number_format($row['average_cost'], 2) }}</td>
                                <td>{{ number_format($row['inventory_value'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ __('vasaccounting::lang.views.inventory.valuation.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const documentsTable = window.VasWorkspace?.initLocalDataTable('#vas-inventory-documents-table', {
                order: [[0, 'desc']],
                pageLength: 10
            });
            const movementsTable = window.VasWorkspace?.initLocalDataTable('#vas-inventory-movements-table', {
                order: [[0, 'desc']],
                pageLength: 10
            });
            const reconciliationTable = window.VasWorkspace?.initLocalDataTable('#vas-inventory-reconciliation-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });
            const valuationTable = window.VasWorkspace?.initLocalDataTable('#vas-inventory-valuation-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });

            if (documentsTable) {
                $('#vas-inventory-documents-search').on('keyup', function () {
                    documentsTable.search(this.value).draw();
                });
            }

            if (movementsTable) {
                $('#vas-inventory-movements-search').on('keyup', function () {
                    movementsTable.search(this.value).draw();
                });
            }

            if (reconciliationTable) {
                $('#vas-inventory-reconciliation-search').on('keyup', function () {
                    reconciliationTable.search(this.value).draw();
                });
            }

            if (valuationTable) {
                $('#vas-inventory-valuation-search').on('keyup', function () {
                    valuationTable.search(this.value).draw();
                });
            }
        });
    </script>
@endsection
