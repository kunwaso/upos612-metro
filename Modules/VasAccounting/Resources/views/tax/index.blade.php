@extends('layouts.app')

@section('title', __('vasaccounting::lang.tax'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.tax'),
        'subtitle' => 'VAT books, tax code usage, and declaration export datasets generated from posted invoice journals.',
    ])

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">VAT summary</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Tax code</th>
                                    <th>Description</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summaries as $row)
                                    <tr>
                                        <td>{{ $row->code }}</td>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ number_format($row->total_debit, 2) }}</td>
                                        <td>{{ number_format($row->total_credit, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Declaration export</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.tax.export') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Provider</label>
                            <select name="provider" class="form-select">
                                @foreach ($providerOptions as $provider)
                                    <option value="{{ $provider }}" @selected($provider === $defaultProvider)>{{ ucfirst($provider) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Export type</label>
                            <select name="export_type" class="form-select">
                                <option value="vat_declaration">VAT declaration</option>
                                <option value="sales_book">Sales VAT book</option>
                                <option value="purchase_book">Purchase VAT book</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Generate export payload</button>
                    </form>

                    @if (session('tax_export_result'))
                        <div class="separator separator-dashed my-6"></div>
                        <div class="fw-bold text-gray-900 mb-2">Latest export preview</div>
                        <div class="text-muted fs-7">Provider: {{ session('tax_export_result.provider') }}</div>
                        <div class="text-muted fs-7">Type: {{ session('tax_export_result.export_type') }}</div>
                        <div class="text-muted fs-7">Generated: {{ session('tax_export_result.generated_at') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mt-1">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Sales VAT book</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Voucher</th>
                                    <th>Customer</th>
                                    <th>Tax code</th>
                                    <th>Tax amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salesVatBook as $row)
                                    <tr>
                                        <td>{{ $row->voucher_no }}</td>
                                        <td>{{ $row->contact_name }}</td>
                                        <td>{{ $row->tax_code }}</td>
                                        <td>{{ number_format((float) $row->tax_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">No output VAT postings yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">Purchase VAT book</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Voucher</th>
                                    <th>Vendor</th>
                                    <th>Tax code</th>
                                    <th>Tax amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseVatBook as $row)
                                    <tr>
                                        <td>{{ $row->voucher_no }}</td>
                                        <td>{{ $row->contact_name }}</td>
                                        <td>{{ $row->tax_code }}</td>
                                        <td>{{ number_format((float) $row->tax_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">No input VAT postings yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mt-1">
        <div class="col-xl-12">
            <div class="card card-flush">
                <div class="card-header"><div class="card-title">Tax code catalog</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Rate</th>
                                    <th>Direction</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($taxCodes as $taxCode)
                                    <tr>
                                        <td>{{ $taxCode->code }}</td>
                                        <td>{{ $taxCode->name }}</td>
                                        <td>{{ number_format($taxCode->rate, 2) }}%</td>
                                        <td>{{ ucfirst($taxCode->direction) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
