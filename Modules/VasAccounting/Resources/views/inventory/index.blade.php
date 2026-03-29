@extends('layouts.app')

@section('title', __('vasaccounting::lang.inventory'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.inventory'),
        'subtitle' => 'Warehouse masters, movement traceability, close-ready valuation, and branch coverage checks for inventory accounting.',
    ])

    @if (session('status.msg'))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="fas fa-check-circle mt-1"></i>
            <div>{{ session('status.msg') }}</div>
        </div>
    @endif

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">SKUs</div><div class="text-gray-900 fw-bold fs-2">{{ $totals['sku_count'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Quantity on hand</div><div class="text-gray-900 fw-bold fs-2">{{ number_format($totals['quantity_on_hand'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Inventory value</div><div class="text-gray-900 fw-bold fs-2">{{ number_format($totals['inventory_value'], 2) }} {{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fs-7">Active warehouses</div><div class="text-gray-900 fw-bold fs-2">{{ $warehouseSummary['active_warehouses'] }}</div><div class="text-muted fs-8 mt-2">{{ $warehouseSummary['uncovered_locations'] }} stock location(s) still need a warehouse master.</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Create warehouse</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.inventory.warehouses.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Warehouse code</label>
                            <input type="text" name="code" class="form-control" placeholder="WH-HQ" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Warehouse name</label>
                            <input type="text" name="name" class="form-control" placeholder="Head office warehouse" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Branch</label>
                            <select name="business_location_id" class="form-select">
                                <option value="">Select branch</option>
                                @foreach ($locationOptions as $locationId => $locationLabel)
                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save warehouse</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Warehouse coverage</div>
                </div>
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <div class="border border-gray-300 rounded p-5 h-100">
                                <div class="text-muted fs-8 mb-2">Warehouse masters</div>
                                <div class="fw-bold fs-2 text-gray-900">{{ $warehouseSummary['warehouse_count'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-gray-300 rounded p-5 h-100">
                                <div class="text-muted fs-8 mb-2">Stock locations</div>
                                <div class="fw-bold fs-2 text-gray-900">{{ $warehouseSummary['stock_locations'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-gray-300 rounded p-5 h-100">
                                <div class="text-muted fs-8 mb-2">Uncovered locations</div>
                                <div class="fw-bold fs-2 text-gray-900">{{ $warehouseSummary['uncovered_locations'] }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="separator my-6"></div>

                    @forelse ($warehouses as $warehouse)
                        <div class="border border-gray-300 rounded p-4 mb-3">
                            <div class="fw-bold text-gray-900">{{ $warehouse->code }} - {{ $warehouse->name }}</div>
                            <div class="text-muted fs-8">{{ optional($warehouse->businessLocation)->name ?: 'No branch linked' }} | {{ ucfirst($warehouse->status) }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No warehouses configured yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Create warehouse document</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.inventory.documents.store') }}">
                        @csrf
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label">Document type</label>
                                <select name="document_type" class="form-select" required>
                                    <option value="receipt">Receipt</option>
                                    <option value="issue">Issue</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="adjustment">Adjustment</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="approved">Approved</option>
                                    <option value="draft">Draft</option>
                                    <option value="pending_approval">Pending approval</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Document date</label>
                                <input type="date" name="document_date" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Posting date</label>
                                <input type="date" name="posting_date" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch</label>
                                <select name="business_location_id" class="form-select">
                                    <option value="">Select branch</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Warehouse</label>
                                <select name="warehouse_id" class="form-select" required>
                                    <option value="">Select warehouse</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->code }} - {{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Destination warehouse</label>
                                <select name="destination_warehouse_id" class="form-select">
                                    <option value="">Select destination</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->code }} - {{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Offset account</label>
                                <select name="offset_account_id" class="form-select">
                                    <option value="">Use default posting map</option>
                                    @foreach ($offsetAccountOptions as $accountId => $accountLabel)
                                        <option value="{{ $accountId }}">{{ $accountLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Product</label>
                                <select name="lines[0][product_id]" class="form-select" required>
                                    <option value="">Select product</option>
                                    @foreach ($productOptions as $productId => $productLabel)
                                        <option value="{{ $productId }}">{{ $productLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" step="0.0001" min="0.0001" name="lines[0][quantity]" class="form-control" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit cost</label>
                                <input type="number" step="0.0001" min="0" name="lines[0][unit_cost]" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Adjustment direction</label>
                                <select name="lines[0][direction]" class="form-select">
                                    <option value="">Auto by document</option>
                                    <option value="increase">Increase</option>
                                    <option value="decrease">Decrease</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Warehouse posting note"></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary btn-sm">Save warehouse document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Recent warehouse documents</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Document</th>
                                    <th>Type</th>
                                    <th>Warehouse</th>
                                    <th>Status</th>
                                    <th>Voucher</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inventoryDocuments as $document)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900">{{ $document->document_no }}</div>
                                            <div class="text-muted fs-8">{{ optional($document->document_date)->format('Y-m-d') }}</div>
                                        </td>
                                        <td>{{ ucfirst($document->document_type) }}</td>
                                        <td>
                                            {{ optional($document->warehouse)->code ?: '-' }}
                                            @if ($document->destinationWarehouse)
                                                <div class="text-muted fs-8">To {{ $document->destinationWarehouse->code }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $document->status === 'posted' ? 'badge-light-success' : ($document->status === 'reversed' ? 'badge-light-danger' : 'badge-light-warning') }}">
                                                {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($document->postedVoucher)->voucher_no ?: '-' }}</td>
                                        <td class="text-end">
                                            @if (in_array($document->status, ['draft', 'pending_approval', 'approved'], true))
                                                <form method="POST" action="{{ route('vasaccounting.inventory.documents.post', $document->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-light-primary">Post</button>
                                                </form>
                                            @elseif ($document->status === 'posted')
                                                <form method="POST" action="{{ route('vasaccounting.inventory.documents.reverse', $document->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-light-danger">Reverse</button>
                                                </form>
                                            @else
                                                <span class="text-muted fs-8">No action</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted">No warehouse documents have been created yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header">
            <div class="card-title">Recent inventory movement</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Product</th>
                            <th>Warehouse branch</th>
                            <th>Qty</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movementRows as $row)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($row->transaction_date)->format('Y-m-d') }}</td>
                                <td class="text-gray-900 fw-semibold">{{ $row->reference }}</td>
                                <td><span class="badge {{ $row->direction === 'in' ? 'badge-light-success' : 'badge-light-danger' }}">{{ ucfirst(str_replace('_', ' ', $row->movement_type)) }}</span></td>
                                <td>{{ $row->sku }} - {{ $row->product_name }}</td>
                                <td>{{ $row->location_name }}</td>
                                <td>{{ number_format((float) $row->quantity, 2) }}</td>
                                <td>{{ number_format((float) $row->movement_value, 2) }} {{ $currency }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">No recent inventory movement was found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header">
            <div class="card-title">Warehouse reconciliation</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Branch</th>
                            <th>Warehouse master</th>
                            <th>SKUs</th>
                            <th>Qty on hand</th>
                            <th>Inventory value</th>
                            <th>Last movement</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reconciliationRows as $row)
                            <tr>
                                <td>{{ $row['location_name'] }}</td>
                                <td>{{ $row['warehouse_code'] ? ($row['warehouse_code'] . ' - ' . $row['warehouse_name']) : 'Missing warehouse master' }}</td>
                                <td>{{ $row['sku_count'] }}</td>
                                <td>{{ number_format($row['qty_available'], 2) }}</td>
                                <td>{{ number_format($row['inventory_value'], 2) }} {{ $currency }}</td>
                                <td>{{ $row['last_movement_at'] ? \Illuminate\Support\Carbon::parse($row['last_movement_at'])->format('Y-m-d') : '-' }}</td>
                                <td>
                                    <span class="badge {{ $row['coverage_status'] === 'aligned' ? 'badge-light-success' : ($row['coverage_status'] === 'missing_master' ? 'badge-light-danger' : 'badge-light-warning') }}">
                                        {{ ucfirst(str_replace('_', ' ', $row['coverage_status'])) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">No reconciliation rows are available yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title">Inventory valuation detail</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Branch</th>
                            <th>Qty Available</th>
                            <th>Average Cost</th>
                            <th>Inventory Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['sku'] }}</td>
                                <td>{{ $row['product_name'] }}</td>
                                <td>{{ $row['location_name'] ?: ('Location #' . $row['location_id']) }}</td>
                                <td>{{ number_format($row['qty_available'], 2) }}</td>
                                <td>{{ number_format($row['average_cost'], 2) }}</td>
                                <td>{{ number_format($row['inventory_value'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
