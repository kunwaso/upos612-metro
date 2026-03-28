@extends('layouts.app')

@section('title', __('vasaccounting::lang.invoices'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.invoices'),
        'subtitle' => 'Sales invoices, purchase invoices, credit notes, debit notes, and e-invoice readiness from the VAS ledger.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fw-semibold fs-7 mb-2">Sales invoices</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['sales_count'] }}</div><div class="text-muted fs-8">{{ number_format((float) $summary['sales_amount'], 2) }} {{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fw-semibold fs-7 mb-2">Purchase invoices</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['purchase_count'] }}</div><div class="text-muted fs-8">{{ number_format((float) $summary['purchase_amount'], 2) }} {{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fw-semibold fs-7 mb-2">Credit / debit notes</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['note_count'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-700 fw-semibold fs-7 mb-2">Issued e-invoices</div><div class="text-gray-900 fw-bold fs-2">{{ $summary['issued_einvoices'] }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Sales documents</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Voucher</th>
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>E-invoice</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salesInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->voucher_no }}</td>
                                        <td>{{ $invoice->contact_name }}</td>
                                        <td>{{ str_replace('_', ' ', $invoice->voucher_type) }}</td>
                                        <td>{{ number_format((float) $invoice->amount, 2) }} {{ $currency }}</td>
                                        <td>{{ $invoice->einvoice_document_no ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No sales invoices posted yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Purchase documents</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Voucher</th>
                                    <th>Vendor</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->voucher_no }}</td>
                                        <td>{{ $invoice->contact_name }}</td>
                                        <td>{{ str_replace('_', ' ', $invoice->voucher_type) }}</td>
                                        <td>{{ number_format((float) $invoice->amount, 2) }} {{ $currency }}</td>
                                        <td><span class="badge badge-light-primary">{{ ucfirst($invoice->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No purchase invoices posted yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
