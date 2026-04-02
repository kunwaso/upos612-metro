@extends('layouts.app')

@section('title', __('vasaccounting::lang.views.payment_documents.show.page_title'))

@section('content')
    @php($paymentMeta = (array) data_get($voucher->meta, 'payment', []))

    @include('vasaccounting::partials.header', [
        'title' => $voucher->voucher_no,
        'subtitle' => $voucher->description ?: __('vasaccounting::lang.views.payment_documents.show.subtitle_fallback'),
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-7">{{ __('vasaccounting::lang.views.payment_documents.show.summary.type') }}</div><div class="fw-bold fs-4">{{ str_replace('_', ' ', $voucher->voucher_type) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-7">{{ __('vasaccounting::lang.views.payment_documents.show.summary.status') }}</div><div class="fw-bold fs-4">{{ str_replace('_', ' ', $voucher->status) }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-7">{{ __('vasaccounting::lang.views.payment_documents.show.summary.reference') }}</div><div class="fw-bold fs-4">{{ $voucher->reference ?: '-' }}</div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-7">{{ __('vasaccounting::lang.views.payment_documents.show.summary.amount') }}</div><div class="fw-bold fs-4">{{ number_format((float) max($voucher->total_debit, $voucher->total_credit), 2) }}</div></div></div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.payment_documents.show.journal_lines_title') }}</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.payment_documents.show.line_no') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.payment_documents.show.account') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.payment_documents.show.description') }}</th>
                                    <th class="text-end">{{ __('vasaccounting::lang.views.payment_documents.show.debit') }}</th>
                                    <th class="text-end">{{ __('vasaccounting::lang.views.payment_documents.show.credit') }}</th>
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
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.payment_documents.show.settlement_targets_title') }}</div>
                </div>
                <div class="card-body">
                    @forelse (data_get($paymentMeta, 'settlement_targets', []) as $target)
                        <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                            <div>
                                <div class="fw-semibold">{{ __('vasaccounting::lang.views.payment_documents.show.target_voucher', ['id' => $target['target_voucher_id']]) }}</div>
                                <div class="text-muted fs-7">{{ $target['target_type'] ?? __('vasaccounting::lang.views.payment_documents.show.target_fallback') }}</div>
                            </div>
                            <div class="text-end">{{ number_format((float) ($target['amount'] ?? 0), 2) }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ __('vasaccounting::lang.views.payment_documents.show.no_settlement_targets') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.payment_documents.show.actions_title') }}</div></div>
                <div class="card-body d-flex flex-column gap-3">
                    @if ($voucher->status === 'draft')
                        <a href="{{ route('vasaccounting.payment_documents.edit', $voucher->id) }}" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.payment_documents.show.edit_draft') }}</a>
                        <form method="POST" action="{{ route('vasaccounting.payment_documents.submit', $voucher->id) }}">@csrf<button type="submit" class="btn btn-light-warning btn-sm w-100">{{ __('vasaccounting::lang.views.payment_documents.show.submit') }}</button></form>
                        <form method="POST" action="{{ route('vasaccounting.payment_documents.post', $voucher->id) }}">@csrf<button type="submit" class="btn btn-primary btn-sm w-100">{{ __('vasaccounting::lang.views.payment_documents.show.post') }}</button></form>
                    @endif
                    @if ($voucher->status === 'pending_approval')
                        <form method="POST" action="{{ route('vasaccounting.payment_documents.approve', $voucher->id) }}">@csrf<button type="submit" class="btn btn-light-success btn-sm w-100">{{ __('vasaccounting::lang.views.payment_documents.show.approve') }}</button></form>
                        <form method="POST" action="{{ route('vasaccounting.payment_documents.reject', $voucher->id) }}">@csrf<button type="submit" class="btn btn-light-danger btn-sm w-100">{{ __('vasaccounting::lang.views.payment_documents.show.reject') }}</button></form>
                    @endif
                    @if (in_array($voucher->status, ['draft', 'pending_approval', 'approved'], true))
                        <form method="POST" action="{{ route('vasaccounting.payment_documents.cancel', $voucher->id) }}">@csrf<button type="submit" class="btn btn-light btn-sm w-100">{{ __('vasaccounting::lang.views.payment_documents.show.cancel') }}</button></form>
                    @endif
                    @if ($voucher->status === 'approved')
                        <form method="POST" action="{{ route('vasaccounting.payment_documents.post', $voucher->id) }}">@csrf<button type="submit" class="btn btn-primary btn-sm w-100">{{ __('vasaccounting::lang.views.payment_documents.show.post_approved_document') }}</button></form>
                    @endif
                    @if ($voucher->status === 'posted')
                        <form method="POST" action="{{ route('vasaccounting.payment_documents.reverse', $voucher->id) }}">@csrf<button type="submit" class="btn btn-light-danger btn-sm w-100">{{ __('vasaccounting::lang.views.payment_documents.show.reverse') }}</button></form>
                    @endif
                    <a href="{{ route('vasaccounting.payment_documents.index') }}" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.payment_documents.show.back_to_register') }}</a>
                </div>
            </div>
            <div class="card card-flush">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.payment_documents.show.document_meta_title') }}</div></div>
                <div class="card-body">
                    <div class="mb-3"><span class="text-muted fs-7">{{ __('vasaccounting::lang.views.payment_documents.show.counterparty') }}</span><div class="fw-semibold">{{ data_get($paymentMeta, 'counterparty_id', $voucher->contact_id) ?: '-' }}</div></div>
                    <div class="mb-3"><span class="text-muted fs-7">{{ __('vasaccounting::lang.views.payment_documents.show.instrument') }}</span><div class="fw-semibold">{{ data_get($paymentMeta, 'instrument') ?: '-' }}</div></div>
                    <div class="mb-3"><span class="text-muted fs-7">{{ __('vasaccounting::lang.views.payment_documents.show.external_reference') }}</span><div class="fw-semibold">{{ $voucher->external_reference ?: '-' }}</div></div>
                    <div><span class="text-muted fs-7">{{ __('vasaccounting::lang.views.payment_documents.show.legacy_transaction') }}</span><div class="fw-semibold">{{ data_get($voucher->meta, 'legacy_links.transaction_id') ?: '-' }}</div></div>
                </div>
            </div>
        </div>
    </div>
@endsection
