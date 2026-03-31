@extends('layouts.app')

@section('title', __('vasaccounting::lang.vouchers'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $voucher->voucher_no,
        'subtitle' => $voucher->description ?: data_get($vasAccountingPageMeta ?? [], 'subtitle', __('vasaccounting::lang.views.vouchers.show.page_subtitle')),
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.views.vouchers.show.summary.type') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $vasAccountingUtil->voucherTypeLabel((string) $voucher->voucher_type) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.views.vouchers.show.summary.posting_date') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->posting_date }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.views.vouchers.show.summary.status') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $vasAccountingUtil->documentStatusLabel((string) $voucher->status) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.views.vouchers.show.summary.reference') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->reference ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.module_area') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $vasAccountingUtil->moduleAreaLabel((string) ($voucher->module_area ?: 'accounting')) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.document_type') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->document_type ?: $voucher->voucher_type }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.submitted_at') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->submitted_at ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fs-7">{{ __('vasaccounting::lang.approved_at') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $voucher->approved_at ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.vouchers.show.journal_lines.title') }}</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>#</th>
                                    <th>{{ __('vasaccounting::lang.views.vouchers.show.journal_lines.account') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.vouchers.show.journal_lines.description') }}</th>
                                    <th class="text-end">{{ __('vasaccounting::lang.views.vouchers.show.journal_lines.debit') }}</th>
                                    <th class="text-end">{{ __('vasaccounting::lang.views.vouchers.show.journal_lines.credit') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($voucher->lines as $line)
                                    <tr>
                                        <td>{{ $line->line_no }}</td>
                                        <td>{{ optional($line->account)->account_code }} - {{ optional($line->account)->account_name }}</td>
                                        <td>{{ $line->description }}</td>
                                        <td class="text-end">{{ number_format((float) $line->debit, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $line->credit, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">{{ __('vasaccounting::lang.views.vouchers.show.journal_lines.totals') }}</td>
                                    <td class="text-end">{{ number_format((float) $voucher->total_debit, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $voucher->total_credit, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.vouchers.show.actions.title') }}</div>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    @if ($voucher->status !== 'posted')
                        <form method="POST" action="{{ route('vasaccounting.vouchers.post', $voucher->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-light-primary btn-sm w-100">{{ __('vasaccounting::lang.views.vouchers.show.actions.post_voucher') }}</button>
                        </form>
                    @endif

                    @if ($voucher->status === 'posted')
                        <form method="POST" action="{{ route('vasaccounting.vouchers.reverse', $voucher->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-light-danger btn-sm w-100">{{ __('vasaccounting::lang.views.vouchers.show.actions.reverse_voucher') }}</button>
                        </form>
                    @endif

                    <a href="{{ route('vasaccounting.vouchers.create') }}" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.vouchers.show.actions.create_new_voucher') }}</a>
                    <a href="{{ route('vasaccounting.vouchers.index') }}" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.vouchers.show.actions.back_to_register') }}</a>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.vouchers.show.audit.title') }}</div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.vouchers.show.audit.debit_total') }}</span>
                        <span class="text-gray-900 fw-semibold">{{ number_format((float) $voucher->total_debit, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.vouchers.show.audit.credit_total') }}</span>
                        <span class="text-gray-900 fw-semibold">{{ number_format((float) $voucher->total_credit, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.vouchers.show.audit.balanced') }}</span>
                        <span class="badge {{ (float) $voucher->total_debit === (float) $voucher->total_credit ? 'badge-light-success' : 'badge-light-danger' }}">
                            {{ (float) $voucher->total_debit === (float) $voucher->total_credit ? __('vasaccounting::lang.views.shared.yes') : __('vasaccounting::lang.views.shared.no') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
