@extends('layouts.app')

@section('title', __('vasaccounting::lang.cash_bank'))

@section('content')
    @php
        $currency = config('vasaccounting.book_currency', 'VND');
        $selectedLocationLabel = !empty($selectedLocationId) ? ($locationOptions[$selectedLocationId] ?? null) : null;
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.cash_bank'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-12">
            <div class="card card-flush">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 py-5">
                    <div>
                        <div class="text-gray-900 fw-bold fs-5">{{ __('vasaccounting::lang.views.cash_bank.scope.title') }}</div>
                        <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.cash_bank.scope.subtitle') }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-primary">{{ __('vasaccounting::lang.views.cash_bank.scope.business_wide') }}</span>
                        <span class="badge badge-light-info">{{ $selectedLocationLabel ? __('vasaccounting::lang.views.cash_bank.scope.location', ['location' => $selectedLocationLabel]) : __('vasaccounting::lang.ui.all_locations') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.cash_bank.cards.cashbooks') }}</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $summary['cashbooks'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.cash_bank.cards.bank_accounts') }}</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $summary['bank_accounts'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.cash_bank.cards.statement_imports') }}</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $summary['statement_imports'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.cash_bank.cards.unmatched_lines') }}</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $summary['unmatched_lines'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.cashbook_form.title') }}</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.cash_bank.cashbooks.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.shared.code') }}</label>
                            <input type="text" name="code" class="form-control form-control-solid" placeholder="CASH-HQ" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.shared.name') }}</label>
                            <input type="text" name="name" class="form-control form-control-solid" placeholder="Head office cashbook" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.shared.branch') }}</label>
                            <select name="business_location_id" class="form-select form-select-solid">
                                <option value="">{{ __('vasaccounting::lang.views.cash_bank.shared.select_branch') }}</option>
                                @foreach ($locationOptions as $locationId => $locationLabel)
                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-6">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.cashbook_form.cash_ledger_account') }}</label>
                            <select name="cash_account_id" class="form-select form-select-solid">
                                <option value="">{{ __('vasaccounting::lang.placeholders.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.cash_bank.cashbook_form.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.bank_account_form.title') }}</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.cash_bank.bank_accounts.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.bank_account_form.account_code') }}</label>
                            <input type="text" name="account_code" class="form-control form-control-solid" placeholder="VCB-HQ" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.bank_account_form.bank_name') }}</label>
                            <input type="text" name="bank_name" class="form-control form-control-solid" placeholder="Vietcombank" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.bank_account_form.account_holder') }}</label>
                            <input type="text" name="account_name" class="form-control form-control-solid" placeholder="UPOS Co., Ltd." required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.bank_account_form.account_number') }}</label>
                            <input type="text" name="account_number" class="form-control form-control-solid" placeholder="0123456789" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.shared.branch') }}</label>
                            <select name="business_location_id" class="form-select form-select-solid">
                                <option value="">{{ __('vasaccounting::lang.views.cash_bank.shared.select_branch') }}</option>
                                @foreach ($locationOptions as $locationId => $locationLabel)
                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-6">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.bank_account_form.ledger_account') }}</label>
                            <select name="ledger_account_id" class="form-select form-select-solid">
                                <option value="">{{ __('vasaccounting::lang.placeholders.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.cash_bank.bank_account_form.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.statement_import.title') }}</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.cash_bank.statements.import') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('bank_statement_provider') }}</label>
                            <select name="provider" class="form-select form-select-solid">
                                @foreach ($providerOptions as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}" @selected($providerKey === $defaultProvider)>{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.statement_import.bank_account') }}</label>
                            <select name="bank_account_id" class="form-select form-select-solid">
                                <option value="">{{ __('vasaccounting::lang.views.cash_bank.statement_import.select_bank_account') }}</option>
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->account_code }} - {{ $bankAccount->bank_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.statement_import.reference_no') }}</label>
                            <input type="text" name="reference_no" class="form-control form-control-solid" placeholder="MAR-2026-01">
                        </div>
                        <div class="mb-6">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.statement_import.statement_lines') }}</label>
                            <textarea name="statement_lines" rows="7" class="form-control form-control-solid" placeholder="{{ __('vasaccounting::lang.views.cash_bank.statement_import.example_placeholder') }}" required></textarea>
                            <div class="text-muted fs-8 mt-2">{{ __('vasaccounting::lang.views.cash_bank.statement_import.line_help') }}</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.cash_bank.statement_import.submit') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.title') }}</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.reconciliation.table.date') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.reconciliation.table.description') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.reconciliation.table.amount') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.reconciliation.table.status') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.reconciliation.table.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($statementLines as $line)
                                    <tr>
                                        <td>{{ optional($line->transaction_date)->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ $line->description }}</div>
                                            <div class="text-muted fs-8">
                                                {{ optional(optional($line->statementImport)->bankAccount)->account_code }}
                                                {{ optional(optional($line->statementImport)->bankAccount)->bank_name }}
                                            </div>
                                        </td>
                                        <td>{{ number_format((float) $line->amount, 2) }} {{ $currency }}</td>
                                        <td>
                                            <span class="badge {{ $line->match_status === 'matched' ? 'badge-light-success' : ($line->match_status === 'ignored' ? 'badge-light-warning' : 'badge-light-danger') }}">
                                                {{ $vasAccountingUtil->matchStatusLabel((string) $line->match_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('vasaccounting.cash_bank.statements.reconcile', $line->id) }}" class="d-flex flex-column gap-2">
                                                @csrf
                                                <select name="matched_voucher_id" class="form-select form-select-sm form-select-solid">
                                                    <option value="">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.select_voucher') }}</option>
                                                    @foreach ($candidateVouchers as $voucher)
                                                        <option value="{{ $voucher->id }}" @selected((int) $line->matched_voucher_id === (int) $voucher->id)>
                                                            {{ $voucher->voucher_no }} | {{ $vasAccountingUtil->voucherTypeLabel((string) $voucher->voucher_type) }} | {{ number_format((float) $voucher->total_debit, 2) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <button type="submit" name="match_status" value="matched" class="btn btn-light-success btn-sm">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.match') }}</button>
                                                    <button type="submit" name="match_status" value="ignored" class="btn btn-light-warning btn-sm">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.ignore') }}</button>
                                                    <button type="submit" name="match_status" value="unmatched" class="btn btn-light-secondary btn-sm">{{ $vasAccountingUtil->actionLabel('clear') }}</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.registers.cashbooks') }}</div>
                </div>
                <div class="card-body">
                    @forelse ($cashbooks as $cashbook)
                        <div class="border border-gray-200 rounded p-4 mb-3">
                            <div class="fw-bold text-gray-900">{{ $cashbook->code }} - {{ $cashbook->name }}</div>
                            <div class="text-muted fs-8">{{ optional($cashbook->businessLocation)->name }} | {{ optional($cashbook->cashAccount)->account_code }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ __('vasaccounting::lang.views.cash_bank.registers.cashbooks_empty') }}</div>
                    @endforelse
                </div>
            </div>
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.registers.bank_accounts') }}</div>
                </div>
                <div class="card-body">
                    @forelse ($bankAccounts as $bankAccount)
                        <div class="border border-gray-200 rounded p-4 mb-3">
                            <div class="fw-bold text-gray-900">{{ $bankAccount->account_code }} - {{ $bankAccount->bank_name }}</div>
                            <div class="text-muted fs-8">{{ $bankAccount->account_name }} | {{ $bankAccount->account_number }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ __('vasaccounting::lang.views.cash_bank.registers.bank_accounts_empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.activity.recent_cash_book') }}</div>
                </div>
                <div class="card-body">
                    @forelse ($cashLedgerRows as $row)
                        <div class="border-bottom border-gray-200 py-3">
                            <div class="fw-semibold text-gray-900">{{ $row->voucher_no }}</div>
                            <div class="text-muted fs-8">{{ $row->posting_date }} | {{ number_format((float) $row->debit, 2) }} / {{ number_format((float) $row->credit, 2) }} {{ $currency }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ __('vasaccounting::lang.views.cash_bank.activity.recent_cash_book_empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.activity.recent_bank_book') }}</div>
                </div>
                <div class="card-body">
                    @forelse ($bankLedgerRows as $row)
                        <div class="border-bottom border-gray-200 py-3">
                            <div class="fw-semibold text-gray-900">{{ $row->voucher_no }}</div>
                            <div class="text-muted fs-8">{{ $row->posting_date }} | {{ number_format((float) $row->debit, 2) }} / {{ number_format((float) $row->credit, 2) }} {{ $currency }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ __('vasaccounting::lang.views.cash_bank.activity.recent_bank_book_empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.activity.statement_imports') }}</div>
                </div>
                <div class="card-body">
                    @forelse ($statementImports as $statementImport)
                        <div class="border-bottom border-gray-200 py-3">
                            <div class="fw-semibold text-gray-900">{{ $statementImport->reference_no ?: __('vasaccounting::lang.views.cash_bank.activity.statement_import_fallback', ['id' => $statementImport->id]) }}</div>
                            <div class="text-muted fs-8">{{ optional($statementImport->imported_at)->format('Y-m-d H:i') }} | {{ $vasAccountingUtil->genericStatusLabel((string) $statementImport->status) }}</div>
                        </div>
                    @empty
                        <div class="text-muted">{{ __('vasaccounting::lang.views.cash_bank.activity.statement_imports_empty') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
