@extends('layouts.app')

@section('title', __('vasaccounting::lang.views.payment_documents.form.page_title_create'))

@section('content')
    @php
        $document = $voucher ?? null;
        $selectedKind = old('payment_kind', $document->voucher_type ?? $selectedPaymentKind ?? data_get($prefill, 'payment_kind', 'bank_payment'));
        $isReceipt = in_array($selectedKind, ['cash_receipt', 'bank_receipt'], true);
        $counterpartyOptions = $isReceipt ? ($contactOptions['receipt'] ?? []) : ($contactOptions['payment'] ?? []);
        $selectedContactId = old('contact_id', data_get($prefill, 'contact_id', $document->contact_id ?? ''));
        $selectedAmount = old('amount', data_get($prefill, 'amount', $document ? max((float) $document->total_debit, (float) $document->total_credit) : 0));
        $selectedCurrency = old('currency_code', $document->currency_code ?? data_get($prefill, 'currency_code', 'VND'));
        $selectedExchangeRate = old('exchange_rate', $document->exchange_rate ?? data_get($prefill, 'exchange_rate', 1));
        $selectedDocumentDate = old('document_date', data_get($prefill, 'document_date', optional($document?->document_date)->toDateString() ?: now()->toDateString()));
        $selectedPostingDate = old('posting_date', data_get($prefill, 'posting_date', optional($document?->posting_date)->toDateString() ?: now()->toDateString()));
        $selectedReference = old('reference', data_get($prefill, 'reference', $document->reference ?? ''));
        $selectedExternalReference = old('external_reference', data_get($prefill, 'external_reference', $document->external_reference ?? ''));
        $selectedDescription = old('description', data_get($prefill, 'description', $document->description ?? ''));
        $selectedInstrument = old('payment_instrument', data_get($prefill, 'payment_instrument', data_get((array) $document?->meta, 'payment.instrument', '')));
        $selectedCashbookId = old('cashbook_id', data_get($prefill, 'cashbook_id', data_get((array) $document?->meta, 'payment.cashbook_id')));
        $selectedBankAccountId = old('bank_account_id', data_get($prefill, 'bank_account_id', data_get((array) $document?->meta, 'payment.bank_account_id')));
        $selectedLocationId = old('business_location_id', data_get($prefill, 'business_location_id', $document->business_location_id ?? ''));
        $payableRows = old('settlement_targets', data_get($prefill, 'settlement_targets', []));
        $paymentRows = collect($payableRows)->keyBy('target_voucher_id');
        $payableItems = collect($payableOpenItems ?? []);
        $receivableItems = collect($receivableOpenItems ?? []);
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.views.payment_documents.form.header_create_title'),
        'subtitle' => __('vasaccounting::lang.views.payment_documents.form.header_create_subtitle'),
    ])

    @if ($errors->any())
        <div class="alert alert-danger mb-8">
            <div class="fw-semibold mb-2">{{ __('vasaccounting::lang.views.payment_documents.form.error_summary') }}</div>
            <ul class="mb-0 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.payment_documents.form.details_title') }}</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.payment_documents.store') }}">
                        @csrf

                        <div class="row g-5 mb-8">
                            <div class="col-md-4">
                                <label class="form-label required">{{ __('vasaccounting::lang.views.payment_documents.form.payment_kind') }}</label>
                                <select class="form-select form-select-solid" name="payment_kind" id="payment-kind-select">
                                    @foreach (($paymentKindOptions ?? $paymentKinds ?? []) as $kindValue => $kindLabel)
                                        <option value="{{ $kindValue }}" {{ $selectedKind === $kindValue ? 'selected' : '' }}>{{ $kindLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">{{ __('vasaccounting::lang.views.payment_documents.form.counterparty') }}</label>
                                <select class="form-select form-select-solid counterparty-select" name="contact_id" id="payment-counterparty-select" {{ $isReceipt ? 'disabled' : '' }}>
                                    <option value="">{{ __('vasaccounting::lang.views.payment_documents.form.select_contact') }}</option>
                                    @foreach (($contactOptions['payment'] ?? []) as $contactId => $contactLabel)
                                        <option value="{{ $contactId }}" {{ ! $isReceipt && (string) $selectedContactId === (string) $contactId ? 'selected' : '' }}>{{ $contactLabel }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select form-select-solid counterparty-select d-none" name="contact_id" id="receipt-counterparty-select" {{ $isReceipt ? '' : 'disabled' }}>
                                    <option value="">{{ __('vasaccounting::lang.views.payment_documents.form.select_contact') }}</option>
                                    @foreach (($contactOptions['receipt'] ?? []) as $contactId => $contactLabel)
                                        <option value="{{ $contactId }}" {{ $isReceipt && (string) $selectedContactId === (string) $contactId ? 'selected' : '' }}>{{ $contactLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.branch') }}</label>
                                <select class="form-select form-select-solid" name="business_location_id">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                    @foreach ($locationOptions as $locationId => $locationName)
                                        <option value="{{ $locationId }}" {{ (string) $selectedLocationId === (string) $locationId ? 'selected' : '' }}>{{ $locationName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-5 mb-8">
                            <div class="col-md-3">
                                <label class="form-label required">{{ __('vasaccounting::lang.views.payment_documents.form.document_date') }}</label>
                                <input type="date" class="form-control form-control-solid" name="document_date" value="{{ $selectedDocumentDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">{{ __('vasaccounting::lang.views.payment_documents.form.posting_date') }}</label>
                                <input type="date" class="form-control form-control-solid" name="posting_date" value="{{ $selectedPostingDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">{{ __('vasaccounting::lang.views.shared.amount') }}</label>
                                <input type="number" min="0.0001" step="0.0001" class="form-control form-control-solid text-end" name="amount" value="{{ $selectedAmount }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('vasaccounting::lang.views.payment_documents.form.currency') }}</label>
                                <input type="text" class="form-control form-control-solid" name="currency_code" value="{{ $selectedCurrency }}">
                            </div>
                        </div>

                        <div class="row g-5 mb-8">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.payment_documents.form.exchange_rate') }}</label>
                                <input type="number" min="0.000001" step="0.000001" class="form-control form-control-solid text-end" name="exchange_rate" value="{{ $selectedExchangeRate }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.payment_documents.form.cashbook') }}</label>
                                <select class="form-select form-select-solid" name="cashbook_id">
                                    <option value="">{{ __('vasaccounting::lang.views.payment_documents.form.auto_cash_account') }}</option>
                                    @foreach ($cashbooks as $cashbook)
                                        <option value="{{ $cashbook->id }}" {{ (string) $selectedCashbookId === (string) $cashbook->id ? 'selected' : '' }}>{{ $cashbook->code }} - {{ $cashbook->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.payment_documents.form.bank_account') }}</label>
                                <select class="form-select form-select-solid" name="bank_account_id">
                                    <option value="">{{ __('vasaccounting::lang.views.payment_documents.form.auto_bank_account') }}</option>
                                    @foreach ($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}" {{ (string) $selectedBankAccountId === (string) $bankAccount->id ? 'selected' : '' }}>{{ $bankAccount->account_code }} - {{ $bankAccount->bank_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-5 mb-8">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.payment_documents.form.payment_instrument') }}</label>
                                <input type="text" class="form-control form-control-solid" name="payment_instrument" value="{{ $selectedInstrument }}" placeholder="{{ __('vasaccounting::lang.views.payment_documents.form.payment_instrument_placeholder') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.reference') }}</label>
                                <input type="text" class="form-control form-control-solid" name="reference" value="{{ $selectedReference }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('vasaccounting::lang.views.payment_documents.form.external_reference') }}</label>
                                <input type="text" class="form-control form-control-solid" name="external_reference" value="{{ $selectedExternalReference }}">
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="form-label">{{ __('vasaccounting::lang.views.shared.description') }}</label>
                            <input type="text" class="form-control form-control-solid" name="description" value="{{ $selectedDescription }}" placeholder="{{ __('vasaccounting::lang.views.payment_documents.form.description_placeholder') }}">
                        </div>

                        <div class="card card-bordered mb-8">
                            <div class="card-header">
                                <div class="card-title fs-6">{{ __('vasaccounting::lang.views.payment_documents.form.settlement_targets_title') }}</div>
                            </div>
                            <div class="card-body">
                                <div class="text-muted fs-7 mb-4">{{ __('vasaccounting::lang.views.payment_documents.form.settlement_targets_subtitle') }}</div>

                                <div id="payment-settlement-table" class="table-responsive">
                                    <table class="table align-middle table-row-dashed fs-7 gy-3">
                                        <thead>
                                            <tr class="text-muted fw-bold text-uppercase">
                                                <th style="width: 60px;">{{ __('vasaccounting::lang.views.payment_documents.form.settlement_table_use') }}</th>
                                                <th>{{ __('vasaccounting::lang.views.payment_documents.form.settlement_table_voucher') }}</th>
                                                <th>{{ __('vasaccounting::lang.views.payment_documents.form.settlement_table_contact') }}</th>
                                                <th class="text-end">{{ __('vasaccounting::lang.views.payment_documents.form.settlement_table_open_amount') }}</th>
                                                <th class="text-end">{{ __('vasaccounting::lang.views.payment_documents.form.settlement_table_allocate') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="payable-targets" {{ $isReceipt ? 'style=display:none;' : '' }}>
                                            @forelse ($payableItems as $item)
                                                @php($target = $paymentRows->get($item->id))
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input settlement-toggle" data-row="payable-{{ $loop->index }}" {{ $target ? 'checked' : '' }}>
                                                    </td>
                                                    <td>{{ $item->voucher_no }}</td>
                                                    <td>{{ $item->contact_name }}</td>
                                                    <td class="text-end">{{ number_format((float) $item->outstanding_amount, 2) }}</td>
                                                    <td class="text-end">
                                                        <input type="hidden" class="settlement-target-id" name="settlement_targets[{{ $loop->index }}][target_voucher_id]" value="{{ $item->id }}" {{ $target ? '' : 'disabled' }}>
                                                        <input type="number" min="0.0001" step="0.0001" class="form-control form-control-solid text-end settlement-amount" id="payable-{{ $loop->index }}" name="settlement_targets[{{ $loop->index }}][amount]" value="{{ data_get($target, 'amount') }}" {{ $target ? '' : 'disabled' }}>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.payment_documents.form.no_payable_items') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tbody id="receivable-targets" {{ $isReceipt ? '' : 'style=display:none;' }}>
                                            @forelse ($receivableItems as $item)
                                                @php($target = $paymentRows->get($item->id))
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input settlement-toggle" data-row="receivable-{{ $loop->index }}" {{ $target ? 'checked' : '' }}>
                                                    </td>
                                                    <td>{{ $item->voucher_no }}</td>
                                                    <td>{{ $item->contact_name }}</td>
                                                    <td class="text-end">{{ number_format((float) $item->outstanding_amount, 2) }}</td>
                                                    <td class="text-end">
                                                        <input type="hidden" class="settlement-target-id" name="settlement_targets[{{ count($payableItems) + $loop->index }}][target_voucher_id]" value="{{ $item->id }}" {{ $target ? '' : 'disabled' }}>
                                                        <input type="number" min="0.0001" step="0.0001" class="form-control form-control-solid text-end settlement-amount" id="receivable-{{ $loop->index }}" name="settlement_targets[{{ count($payableItems) + $loop->index }}][amount]" value="{{ data_get($target, 'amount') }}" {{ $target ? '' : 'disabled' }}>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.payment_documents.form.no_receivable_items') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('vasaccounting.payment_documents.index') }}" class="btn btn-light">{{ __('vasaccounting::lang.views.payment_documents.form.back_to_register') }}</a>
                            <div class="d-flex gap-3">
                                <button type="submit" name="action" value="save_draft" class="btn btn-light-primary">{{ __('vasaccounting::lang.views.payment_documents.form.save_draft') }}</button>
                                <button type="submit" name="action" value="submit" class="btn btn-light-warning">{{ __('vasaccounting::lang.views.payment_documents.form.submit') }}</button>
                                <button type="submit" name="action" value="save_and_post" class="btn btn-primary">{{ __('vasaccounting::lang.views.payment_documents.form.save_and_post') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.payment_documents.form.workflow_notes_title') }}</div>
                </div>
                <div class="card-body">
                    <div class="text-muted fs-7 mb-3">{{ __('vasaccounting::lang.views.payment_documents.form.workflow_note_1') }}</div>
                    <div class="text-muted fs-7 mb-3">{{ __('vasaccounting::lang.views.payment_documents.form.workflow_note_2') }}</div>
                    <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.payment_documents.form.workflow_note_3') }}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentKindSelect = document.getElementById('payment-kind-select');
            const paymentCounterpartySelect = document.getElementById('payment-counterparty-select');
            const receiptCounterpartySelect = document.getElementById('receipt-counterparty-select');
            const payableTargets = document.getElementById('payable-targets');
            const receivableTargets = document.getElementById('receivable-targets');

            function syncVisibility() {
                const isReceipt = ['cash_receipt', 'bank_receipt'].includes(paymentKindSelect.value);

                paymentCounterpartySelect.disabled = isReceipt;
                receiptCounterpartySelect.disabled = ! isReceipt;
                paymentCounterpartySelect.classList.toggle('d-none', isReceipt);
                receiptCounterpartySelect.classList.toggle('d-none', ! isReceipt);

                payableTargets.style.display = isReceipt ? 'none' : '';
                receivableTargets.style.display = isReceipt ? '' : 'none';

                payableTargets.querySelectorAll('.settlement-toggle').forEach(function (checkbox) {
                    const row = checkbox.closest('tr');
                    const isActive = ! isReceipt && checkbox.checked;

                    row.querySelectorAll('.settlement-target-id, .settlement-amount').forEach(function (field) {
                        field.disabled = ! isActive;
                    });

                    if (! isActive) {
                        row.querySelector('.settlement-amount').value = '';
                    }
                });

                receivableTargets.querySelectorAll('.settlement-toggle').forEach(function (checkbox) {
                    const row = checkbox.closest('tr');
                    const isActive = isReceipt && checkbox.checked;

                    row.querySelectorAll('.settlement-target-id, .settlement-amount').forEach(function (field) {
                        field.disabled = ! isActive;
                    });

                    if (! isActive) {
                        row.querySelector('.settlement-amount').value = '';
                    }
                });
            }

            document.querySelectorAll('.settlement-toggle').forEach(function (checkbox) {
                checkbox.addEventListener('change', syncVisibility);
            });

            paymentKindSelect.addEventListener('change', syncVisibility);
            syncVisibility();
        });
    </script>
@endsection
