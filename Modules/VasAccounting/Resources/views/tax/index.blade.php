@extends('layouts.app')

@section('title', __('vasaccounting::lang.tax'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.tax'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.tax.cards.tax_codes') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $taxStats['tax_codes']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.tax.cards.tax_codes_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.tax.cards.summary_rows') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $taxStats['summary_rows']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.tax.cards.summary_rows_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.tax.cards.sales_vat') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $taxStats['sales_tax_total'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.tax.cards.sales_vat_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.tax.cards.purchase_vat') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $taxStats['purchase_tax_total'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.tax.cards.purchase_vat_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.tax.summary.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.tax.summary.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.tax.summary.table.tax_code') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.tax.summary.table.description') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.tax.summary.table.debit') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.tax.summary.table.credit') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summaries as $row)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $row->code }}</td>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ number_format((float) $row->total_debit, 2) }}</td>
                                        <td>{{ number_format((float) $row->total_credit, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.tax.summary.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.tax.export.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.tax.export.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.tax.export') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('tax_export_provider') }}</label>
                            <select name="provider" class="form-select">
                                @foreach ($providerOptions as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}" @selected($providerKey === $defaultProvider)>{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.tax.export.export_type') }}</label>
                            <select name="export_type" class="form-select">
                                <option value="vat_declaration">{{ __('vasaccounting::lang.views.tax.export.vat_declaration') }}</option>
                                <option value="sales_book">{{ __('vasaccounting::lang.views.tax.export.sales_book') }}</option>
                                <option value="purchase_book">{{ __('vasaccounting::lang.views.tax.purchase_book.title') }}</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('vasaccounting::lang.views.tax.export.submit') }}</button>
                    </form>

                    @if (session('tax_export_result'))
                        <div class="separator separator-dashed my-6"></div>
                        <div class="fw-bold text-gray-900 mb-2">{{ __('vasaccounting::lang.views.tax.export.preview_title') }}</div>
                        <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.tax.export.provider_label', ['provider' => $vasAccountingUtil->providerLabel((string) session('tax_export_result.provider'), 'tax_export_adapters')]) }}</div>
                        <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.tax.export.export_type_label', ['type' => session('tax_export_result.export_type')]) }}</div>
                        <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.tax.export.generated_at_label', ['time' => session('tax_export_result.generated_at')]) }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.tax.sales_book.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.tax.sales_book.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.tax.sales_book.table.document') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.customer') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.tax.tax_code_label') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.tax.sales_book.table.tax_amount') }}</th>
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
                                    <tr>
                                        <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.tax.sales_book.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.tax.purchase_book.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.tax.purchase_book.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.tax.purchase_book.table.document') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.vendor') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.tax.tax_code_label') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.tax.purchase_book.table.tax_amount') }}</th>
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
                                    <tr>
                                        <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.tax.purchase_book.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.tax.tax_code_register.title') }}</span>
                <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.tax.tax_code_register.subtitle') }}</span>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.shared.code') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.name') }}</th>
                            <th>{{ __('vasaccounting::lang.views.tax.tax_code_register.rate') }}</th>
                            <th>{{ __('vasaccounting::lang.views.tax.tax_code_register.direction') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($taxCodes as $taxCode)
                            <tr>
                                <td class="fw-semibold text-gray-900">{{ $taxCode->code }}</td>
                                <td>{{ $taxCode->name }}</td>
                                <td>{{ number_format((float) $taxCode->rate, 2) }}%</td>
                                <td>{{ $vasAccountingUtil->genericStatusLabel((string) $taxCode->direction) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.tax.tax_code_register.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
