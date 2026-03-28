@extends('layouts.app')

@section('title', __('vasaccounting::lang.inventory'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.inventory'),
        'subtitle' => 'Warehouse masters, movement traceability, close-ready valuation, and branch coverage checks for inventory accounting.',
    ])

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
