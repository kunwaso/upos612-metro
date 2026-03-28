@extends('layouts.app')

@section('title', __('vasaccounting::lang.payables'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.payables'),
        'subtitle' => 'Vendor bill allocations, aging, and payment availability from posted VAS vouchers.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fw-semibold fs-7 mb-2">Outstanding</div><div class="text-gray-900 fw-bold fs-2">{{ number_format((float) $aging['total'], 2) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fw-semibold fs-7 mb-2">1-30 days</div><div class="text-gray-900 fw-bold fs-2">{{ number_format((float) $aging['days_1_30'], 2) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fw-semibold fs-7 mb-2">31-60 days</div><div class="text-gray-900 fw-bold fs-2">{{ number_format((float) $aging['days_31_60'], 2) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fw-semibold fs-7 mb-2">90+ days</div><div class="text-gray-900 fw-bold fs-2">{{ number_format((float) $aging['days_90_plus'], 2) }}</div></div></div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Allocate payment</div></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.payables.allocations.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Bill voucher</label>
                            <select name="bill_voucher_id" class="form-select" required>
                                <option value="">Select bill</option>
                                @foreach ($openItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->voucher_no }} | {{ $item->contact_name }} | {{ number_format((float) $item->outstanding_amount, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Payment voucher</label>
                            <select name="payment_voucher_id" class="form-select" required>
                                <option value="">Select payment</option>
                                @foreach ($paymentItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->voucher_no }} | {{ $item->contact_name }} | {{ number_format((float) $item->available_amount, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Allocation date</label>
                            <input type="date" name="allocation_date" value="{{ now()->format('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save allocation</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Open vendor bills</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Bill</th>
                                    <th>Vendor</th>
                                    <th>Posting date</th>
                                    <th>Outstanding</th>
                                    <th>Age</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($openItems as $item)
                                    <tr>
                                        <td>{{ $item->voucher_no }}</td>
                                        <td>{{ $item->contact_name }}</td>
                                        <td>{{ $item->posting_date }}</td>
                                        <td>{{ number_format((float) $item->outstanding_amount, 2) }} {{ $currency }}</td>
                                        <td>{{ $item->age_days }} days</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No outstanding payables.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Available payments</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Payment</th>
                                    <th>Vendor</th>
                                    <th>Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($paymentItems as $item)
                                    <tr>
                                        <td>{{ $item->voucher_no }}</td>
                                        <td>{{ $item->contact_name }}</td>
                                        <td>{{ number_format((float) $item->available_amount, 2) }} {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">No unallocated payments.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Recent allocations</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th>Bill</th>
                                    <th>Payment</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentAllocations as $allocation)
                                    <tr>
                                        <td>{{ $allocation->allocation_date }}</td>
                                        <td>{{ $allocation->contact_name }}</td>
                                        <td>{{ $allocation->bill_voucher_no }}</td>
                                        <td>{{ $allocation->payment_voucher_no }}</td>
                                        <td>{{ number_format((float) $allocation->amount, 2) }} {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No allocations recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
