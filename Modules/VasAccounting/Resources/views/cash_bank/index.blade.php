@extends('layouts.app')

@section('title', __('vasaccounting::lang.cash_bank'))

@section('content')
    @php
        $currency = config('vasaccounting.book_currency', 'VND');
        $selectedLocationLabel = !empty($selectedLocationId) ? ($locationOptions[$selectedLocationId] ?? null) : null;
        $clearFocusParams = array_filter([
            'period_id' => $closePeriod?->id,
        ]);
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.cash_bank'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    @if ($closePeriod)
        <div class="alert alert-warning d-flex align-items-start mb-8">
            <span class="svg-icon svg-icon-2hx svg-icon-warning me-4">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.3" d="M12 2L2 22H22L12 2Z" fill="currentColor"/>
                    <path d="M12 8V13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="12" cy="17" r="1.5" fill="currentColor"/>
                </svg>
            </span>
            <div>
                <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.cash_bank.close_scope.title', ['period' => $vasAccountingUtil->localizedPeriodName($closePeriod->name)]) }}</div>
                <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.cash_bank.close_scope.subtitle', ['start' => optional($closePeriod->start_date)->format('Y-m-d'), 'end' => optional($closePeriod->end_date)->format('Y-m-d')]) }}</div>
            </div>
        </div>
    @endif

    @if ($workspaceFocus)
        <div class="alert alert-info d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 mb-8">
            <div>
                <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.cash_bank.focus.title', ['focus' => $workspaceFocusLabel]) }}</div>
                <div class="text-muted fs-7">
                    @if ($workspaceFocus === 'treasury_exceptions')
                        {{ __('vasaccounting::lang.views.cash_bank.focus.exception_subtitle', ['statuses' => collect($exceptionStatusFilter)->map(fn ($status) => \Illuminate\Support\Str::headline((string) $status))->implode(', ')]) }}
                    @else
                        {{ __('vasaccounting::lang.views.cash_bank.focus.pending_subtitle') }}
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('vasaccounting.cash_bank.index', $clearFocusParams) }}" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.cash_bank.focus.clear') }}</a>
                @if ($workspaceFocus === 'pending_documents')
                    <a href="#native-treasury-documents" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.cash_bank.focus.jump_pending') }}</a>
                @else
                    <a href="#treasury-exception-queue" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.cash_bank.focus.jump_exceptions') }}</a>
                @endif
            </div>
        </div>
    @endif

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
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.cash_bank.cards.open_exceptions') }}</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $treasuryExceptionSummary['open'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.cash_bank.cards.suggested_matches') }}</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $treasuryExceptionSummary['suggested'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.cash_bank.cards.resolved_exceptions') }}</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $treasuryExceptionSummary['resolved'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div>
                        <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.native_documents.register_title') }}</div>
                        <div class="text-muted fs-7 mt-1">{{ __('vasaccounting::lang.views.cash_bank.native_documents.subtitle') }}</div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.cash_bank.treasury_documents.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.native_documents.document_type') }}</label>
                            <select name="document_type" class="form-select form-select-solid" required>
                                <option value="cash_transfer">Cash transfer</option>
                                <option value="bank_transfer">Bank transfer</option>
                                <option value="petty_cash_expense">Petty cash expense</option>
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.native_documents.document_no') }}</label>
                            <input type="text" name="document_no" class="form-control form-control-solid" placeholder="TRS-2026-001" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.native_documents.reference') }}</label>
                            <input type="text" name="external_reference" class="form-control form-control-solid" placeholder="REF-001">
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
                        <div class="row g-3 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.native_documents.document_date') }}</label>
                                <input type="date" name="document_date" class="form-control form-control-solid" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.native_documents.posting_date') }}</label>
                                <input type="date" name="posting_date" class="form-control form-control-solid" value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.native_documents.source_account') }}</label>
                            <select name="source_account_id" class="form-select form-select-solid" required>
                                <option value="">{{ __('vasaccounting::lang.placeholders.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.native_documents.target_account') }}</label>
                            <select name="target_account_id" class="form-select form-select-solid" required>
                                <option value="">{{ __('vasaccounting::lang.placeholders.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.native_documents.amount') }}</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-solid" required>
                        </div>
                        <div class="mb-6">
                            <label class="form-label">{{ __('vasaccounting::lang.views.cash_bank.native_documents.description') }}</label>
                            <input type="text" name="description" class="form-control form-control-solid" placeholder="Cash moved from store safe to bank deposit" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.cash_bank.native_documents.create') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100" id="native-treasury-documents">
                <div class="card-header">
                    <div>
                        <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.native_documents.title') }}</div>
                        <div class="text-muted fs-7 mt-1">{{ __('vasaccounting::lang.views.cash_bank.native_documents.subtitle') }}</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.native_documents.table.document') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.native_documents.table.type') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.native_documents.table.amount') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.native_documents.table.status') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.native_documents.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($nativeTreasuryDocuments as $document)
                                    <tr>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ $document->document_no }}</div>
                                            <div class="text-muted fs-8">{{ optional($document->document_date)->format('Y-m-d') }} | {{ $document->external_reference }}</div>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::headline((string) str_replace('_', ' ', $document->document_type)) }}</td>
                                        <td>{{ number_format((float) $document->gross_amount, 2) }} {{ $currency }}</td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge badge-light-primary">{{ \Illuminate\Support\Str::headline((string) str_replace('_', ' ', $document->workflow_status)) }}</span>
                                                <span class="badge badge-light-secondary">{{ \Illuminate\Support\Str::headline((string) str_replace('_', ' ', $document->accounting_status)) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                @if ($document->workflow_status === 'draft')
                                                    <form method="POST" action="{{ route('vasaccounting.cash_bank.treasury_documents.submit', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.cash_bank.native_documents.actions.submit') }}</button>
                                                    </form>
                                                @endif
                                                @if ($document->workflow_status === 'submitted')
                                                    <form method="POST" action="{{ route('vasaccounting.cash_bank.treasury_documents.approve', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-success btn-sm">{{ __('vasaccounting::lang.views.cash_bank.native_documents.actions.approve') }}</button>
                                                    </form>
                                                @endif
                                                @if ($document->workflow_status === 'approved' && $document->accounting_status === 'ready_to_post')
                                                    <form method="POST" action="{{ route('vasaccounting.cash_bank.treasury_documents.post', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-success btn-sm">{{ __('vasaccounting::lang.views.cash_bank.native_documents.actions.post') }}</button>
                                                    </form>
                                                @endif
                                                @if ($document->workflow_status === 'posted')
                                                    <form method="POST" action="{{ route('vasaccounting.cash_bank.treasury_documents.reverse', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-danger btn-sm">{{ __('vasaccounting::lang.views.cash_bank.native_documents.actions.reverse') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.cash_bank.native_documents.register_empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
                    <div>
                        <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.title') }}</div>
                        <div class="text-muted fs-7 mt-1">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.subtitle') }}</div>
                    </div>
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
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.reconciliation.table.canonical') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.cash_bank.reconciliation.table.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($statementLines as $line)
                                    @php
                                        $treasuryException = $line->treasuryException;
                                        $topCandidate = data_get($treasuryException?->meta, 'top_candidate');
                                        $canonicalMatch = data_get($line->meta, 'canonical_treasury_match');
                                        $canonicalExceptionStatus = $treasuryException?->status;
                                        $canonicalExceptionBadge = match ($canonicalExceptionStatus) {
                                            'resolved' => 'badge-light-success',
                                            'suggested' => 'badge-light-info',
                                            'ignored' => 'badge-light-warning',
                                            default => 'badge-light-danger',
                                        };
                                    @endphp
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
                                            @if ($canonicalMatch)
                                                <div class="border border-success border-dashed rounded p-3 mb-2">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                        <span class="badge badge-light-success">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.canonical_match') }}</span>
                                                        <span class="text-gray-900 fw-semibold">{{ data_get($canonicalMatch, 'document_no') }}</span>
                                                    </div>
                                                    <div class="text-muted fs-8">
                                                        {{ \Illuminate\Support\Str::headline((string) str_replace('_', ' ', data_get($canonicalMatch, 'document_type'))) }}
                                                        | {{ number_format((float) data_get($canonicalMatch, 'matched_amount', 0), 2) }} {{ $currency }}
                                                    </div>
                                                    @if (data_get($canonicalMatch, 'reconciliation_id'))
                                                        <form method="POST" action="{{ route('vasaccounting.cash_bank.reconciliations.reverse', data_get($canonicalMatch, 'reconciliation_id')) }}" class="mt-3">
                                                            @csrf
                                                            <button type="submit" class="btn btn-light-danger btn-sm">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.reverse_canonical') }}</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @elseif ($treasuryException)
                                                <div class="border border-gray-200 rounded p-3 mb-2">
                                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                        <span class="badge {{ $canonicalExceptionBadge }}">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.canonical_exception') }}</span>
                                                        <span class="badge badge-light-secondary">
                                                            {{ \Illuminate\Support\Str::headline((string) str_replace('_', ' ', $canonicalExceptionStatus)) }}
                                                        </span>
                                                    </div>
                                                    <div class="text-gray-900 fw-semibold">
                                                        {{ $treasuryException->message }}
                                                    </div>
                                                    @if ($topCandidate)
                                                        <div class="text-muted fs-8 mt-1">
                                                            {{ __('vasaccounting::lang.views.cash_bank.reconciliation.recommended_document') }}:
                                                            {{ data_get($topCandidate, 'document_no') }}
                                                            ({{ \Illuminate\Support\Str::headline((string) str_replace('_', ' ', data_get($topCandidate, 'document_type'))) }})
                                                        </div>
                                                        <div class="text-muted fs-8">
                                                            {{ __('vasaccounting::lang.views.cash_bank.reconciliation.recommended_score') }}:
                                                            {{ number_format((float) data_get($topCandidate, 'score', 0), 0) }}
                                                            | {{ __('vasaccounting::lang.views.cash_bank.reconciliation.candidate_count', ['count' => data_get($treasuryException->meta, 'candidate_count', 0)]) }}
                                                        </div>
                                                        @if ($treasuryException->recommended_document_id)
                                                            <form method="POST" action="{{ route('vasaccounting.cash_bank.statements.canonical_reconcile', $line->id) }}" class="mt-3">
                                                                @csrf
                                                                <input type="hidden" name="finance_document_id" value="{{ $treasuryException->recommended_document_id }}">
                                                                <button type="submit" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.apply_recommendation') }}</button>
                                                            </form>
                                                        @endif
                                                    @else
                                                        <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.no_recommendation') }}</div>
                                                    @endif
                                                    <div class="d-flex flex-column gap-2 mt-3">
                                                        <form method="POST" action="{{ route('vasaccounting.cash_bank.statements.refresh_exception', $line->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-light-info btn-sm">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.refresh_candidates') }}</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('vasaccounting.cash_bank.statements.ignore_exception', $line->id) }}" class="d-flex flex-column gap-2">
                                                            @csrf
                                                            <input type="text" name="reason" class="form-control form-control-sm form-control-solid" placeholder="{{ __('vasaccounting::lang.views.cash_bank.reconciliation.ignore_reason_placeholder') }}" required>
                                                            <button type="submit" class="btn btn-light-warning btn-sm">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.ignore_with_reason') }}</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.view_queue') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-muted fs-8 mb-2">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.legacy_fallback') }}</div>
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
                                        <td colspan="6" class="text-muted">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush mb-5" id="treasury-exception-queue">
                <div class="card-header">
                    <div>
                        <div class="card-title">{{ __('vasaccounting::lang.views.cash_bank.treasury_queue.title') }}</div>
                        <div class="text-muted fs-7 mt-1">{{ __('vasaccounting::lang.views.cash_bank.treasury_queue.subtitle') }}</div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse ($treasuryExceptionQueue as $exceptionRow)
                        <div class="border border-gray-200 rounded p-4 mb-3">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="badge {{ ($exceptionRow['status'] ?? null) === 'suggested' ? 'badge-light-info' : 'badge-light-danger' }}">
                                    {{ \Illuminate\Support\Str::headline((string) str_replace('_', ' ', $exceptionRow['status'] ?? 'open')) }}
                                </span>
                                <span class="text-muted fs-8">{{ __('vasaccounting::lang.views.cash_bank.treasury_queue.line', ['id' => $exceptionRow['statement_line_id']]) }}</span>
                            </div>
                            <div class="fw-bold text-gray-900">{{ $exceptionRow['statement_description'] }}</div>
                            <div class="text-muted fs-8 mb-2">
                                {{ $exceptionRow['statement_date'] }}
                                | {{ number_format((float) ($exceptionRow['statement_amount'] ?? 0), 2) }} {{ $currency }}
                                @if (! empty($exceptionRow['bank_account_code']))
                                    | {{ $exceptionRow['bank_account_code'] }}{{ ! empty($exceptionRow['bank_account_name']) ? ' - ' . $exceptionRow['bank_account_name'] : '' }}
                                @endif
                            </div>
                            <div class="text-gray-700 fs-8 mb-2">{{ $exceptionRow['message'] }}</div>
                            @if (! empty($exceptionRow['recommended_document_no']))
                                <div class="text-muted fs-8">
                                    {{ __('vasaccounting::lang.views.cash_bank.reconciliation.recommended_document') }}:
                                    {{ $exceptionRow['recommended_document_no'] }}
                                    ({{ \Illuminate\Support\Str::headline((string) str_replace('_', ' ', $exceptionRow['recommended_document_type'] ?? ''))) }})
                                </div>
                                @if (! empty($exceptionRow['recommended_document_id']))
                                    <form method="POST" action="{{ route('vasaccounting.cash_bank.statements.canonical_reconcile', $exceptionRow['statement_line_id']) }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="finance_document_id" value="{{ $exceptionRow['recommended_document_id'] }}">
                                        <button type="submit" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.apply_recommendation') }}</button>
                                    </form>
                                @endif
                            @endif
                            <div class="text-muted fs-8">
                                {{ __('vasaccounting::lang.views.cash_bank.treasury_queue.score', ['score' => number_format((float) ($exceptionRow['top_match_score'] ?? 0), 0)]) }}
                            </div>
                            <div class="d-flex flex-column gap-2 mt-3">
                                <form method="POST" action="{{ route('vasaccounting.cash_bank.statements.refresh_exception', $exceptionRow['statement_line_id']) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-light-info btn-sm">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.refresh_candidates') }}</button>
                                </form>
                                <form method="POST" action="{{ route('vasaccounting.cash_bank.statements.ignore_exception', $exceptionRow['statement_line_id']) }}" class="d-flex flex-column gap-2">
                                    @csrf
                                    <input type="text" name="reason" class="form-control form-control-sm form-control-solid" placeholder="{{ __('vasaccounting::lang.views.cash_bank.reconciliation.ignore_reason_placeholder') }}" required>
                                    <button type="submit" class="btn btn-light-warning btn-sm">{{ __('vasaccounting::lang.views.cash_bank.reconciliation.ignore_with_reason') }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">{{ __('vasaccounting::lang.views.cash_bank.treasury_queue.empty') }}</div>
                    @endforelse
                </div>
            </div>
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
