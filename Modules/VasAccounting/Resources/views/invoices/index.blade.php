@extends('layouts.app')

@section('title', __('vasaccounting::lang.invoices'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.invoices'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="d-flex flex-wrap gap-3 mb-8">
        <a href="{{ route('vasaccounting.invoices.create', ['invoice_kind' => 'sales_invoice']) }}" class="btn btn-light-primary btn-sm">New sales invoice</a>
        <a href="{{ route('vasaccounting.invoices.create', ['invoice_kind' => 'purchase_invoice']) }}" class="btn btn-primary btn-sm">New purchase invoice</a>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.invoices.cards.sales_invoices') }}</div><div class="text-gray-900 fw-bold fs-1">{{ $summary['sales_count'] }}</div><div class="text-muted fs-8">{{ number_format((float) $summary['sales_amount'], 2) }} {{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.invoices.cards.purchase_invoices') }}</div><div class="text-gray-900 fw-bold fs-1">{{ $summary['purchase_count'] }}</div><div class="text-muted fs-8">{{ number_format((float) $summary['purchase_amount'], 2) }} {{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.invoices.cards.credit_debit_notes') }}</div><div class="text-gray-900 fw-bold fs-1">{{ $summary['note_count'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.invoices.cards.issued_einvoices') }}</div><div class="text-gray-900 fw-bold fs-1">{{ $summary['issued_einvoices'] }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.invoices.sales_queue.title') }}</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.invoices.sales_queue.table.voucher') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.invoices.sales_queue.table.customer') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.invoices.sales_queue.table.type') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.invoices.sales_queue.table.amount') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.invoices.sales_queue.table.einvoice') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salesInvoices as $invoice)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $invoice->voucher_no }}</td>
                                        <td>{{ $invoice->contact_name }}</td>
                                        <td><span class="badge badge-light-primary">{{ $vasAccountingUtil->voucherTypeLabel((string) $invoice->voucher_type) }}</span></td>
                                        <td>{{ number_format((float) $invoice->amount, 2) }} {{ $currency }}</td>
                                        <td>{{ $invoice->einvoice_document_no ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.invoices.sales_queue.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.invoices.purchase_queue.title') }}</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.invoices.purchase_queue.table.voucher') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.invoices.purchase_queue.table.vendor') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.invoices.purchase_queue.table.type') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.invoices.purchase_queue.table.amount') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.invoices.purchase_queue.table.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseInvoices as $invoice)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $invoice->voucher_no }}</td>
                                        <td>{{ $invoice->contact_name }}</td>
                                        <td><span class="badge badge-light-info">{{ $vasAccountingUtil->voucherTypeLabel((string) $invoice->voucher_type) }}</span></td>
                                        <td>{{ number_format((float) $invoice->amount, 2) }} {{ $currency }}</td>
                                        <td><span class="badge badge-light-warning">{{ $vasAccountingUtil->documentStatusLabel((string) $invoice->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.invoices.purchase_queue.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
