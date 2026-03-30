@extends('layouts.app')

@section('title', __('vasaccounting::lang.cash_bank'))

@section('content')
    @php
        $currency = config('vasaccounting.book_currency', 'VND');
        $selectedLocationLabel = !empty($selectedLocationId) ? ($locationOptions[$selectedLocationId] ?? null) : null;
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.cash_bank'),
        'subtitle' => 'Cashbooks, bank masters, statement imports, and reconciliation queues tied to posted VAS vouchers.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-12">
            <div class="card card-flush">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 py-5">
                    <div>
                        <div class="text-gray-900 fw-bold fs-5">Operations Scope</div>
                        <div class="text-muted fs-7">Metrics and queues below reflect the active location filter.</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-light-primary">Business-wide controls preserved</span>
                        <span class="badge badge-light-info">{{ $selectedLocationLabel ? 'Location: ' . $selectedLocationLabel : 'All locations' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Cashbooks</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $summary['cashbooks'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Bank Accounts</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $summary['bank_accounts'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Statement Imports</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $summary['statement_imports'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Unmatched Lines</div>
                    <div class="text-gray-900 fw-bold fs-1">{{ $summary['unmatched_lines'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Create Cashbook</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.cash_bank.cashbooks.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control form-control-solid" placeholder="CASH-HQ" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control form-control-solid" placeholder="Head office cashbook" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Branch</label>
                            <select name="business_location_id" class="form-select form-select-solid">
                                <option value="">Select branch</option>
                                @foreach ($locationOptions as $locationId => $locationLabel)
                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-6">
                            <label class="form-label">Cash Ledger Account</label>
                            <select name="cash_account_id" class="form-select form-select-solid">
                                <option value="">Select account</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save cashbook</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Create Bank Account</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.cash_bank.bank_accounts.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">Account Code</label>
                            <input type="text" name="account_code" class="form-control form-control-solid" placeholder="VCB-HQ" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control form-control-solid" placeholder="Vietcombank" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Account Holder</label>
                            <input type="text" name="account_name" class="form-control form-control-solid" placeholder="UPOS Co., Ltd." required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" class="form-control form-control-solid" placeholder="0123456789" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Branch</label>
                            <select name="business_location_id" class="form-select form-select-solid">
                                <option value="">Select branch</option>
                                @foreach ($locationOptions as $locationId => $locationLabel)
                                    <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-6">
                            <label class="form-label">Tài khoản sổ cái ngân hàng</label>
                            <select name="ledger_account_id" class="form-select form-select-solid">
                                <option value="">{{ __('vasaccounting::lang.placeholders.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Lưu tài khoản ngân hàng</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Nhập sao kê ngân hàng</div>
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
                            <label class="form-label">Tài khoản ngân hàng</label>
                            <select name="bank_account_id" class="form-select form-select-solid">
                                <option value="">Chọn tài khoản ngân hàng</option>
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->account_code }} - {{ $bankAccount->bank_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Mã tham chiếu sao kê</label>
                            <input type="text" name="reference_no" class="form-control form-control-solid" placeholder="MAR-2026-01">
                        </div>
                        <div class="mb-6">
                            <label class="form-label">Dòng sao kê</label>
                            <textarea name="statement_lines" rows="7" class="form-control form-control-solid" placeholder="2026-03-01|Incoming transfer INV-001|15000000|15000000&#10;2026-03-02|Bank fee|-25000|14975000" required></textarea>
                            <div class="text-muted fs-8 mt-2">Mỗi giao dịch một dòng: <code>YYYY-MM-DD|Diễn giải|Số tiền|Số dư cuối dòng(tùy chọn)</code></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Nhập sao kê</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Hàng đợi đối chiếu</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Ngày</th>
                                    <th>Diễn giải</th>
                                    <th>Số tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác đối chiếu</th>
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
                                                    <option value="">Chọn chứng từ</option>
                                                    @foreach ($candidateVouchers as $voucher)
                                                        <option value="{{ $voucher->id }}" @selected((int) $line->matched_voucher_id === (int) $voucher->id)>
                                                            {{ $voucher->voucher_no }} | {{ $vasAccountingUtil->voucherTypeLabel((string) $voucher->voucher_type) }} | {{ number_format((float) $voucher->total_debit, 2) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <button type="submit" name="match_status" value="matched" class="btn btn-light-success btn-sm">Khớp</button>
                                                    <button type="submit" name="match_status" value="ignored" class="btn btn-light-warning btn-sm">Bỏ qua</button>
                                                    <button type="submit" name="match_status" value="unmatched" class="btn btn-light-secondary btn-sm">{{ $vasAccountingUtil->actionLabel('clear') }}</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">Chưa có dòng sao kê nào được nhập.</td>
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
                    <div class="card-title">Cashbooks</div>
                </div>
                <div class="card-body">
                    @forelse ($cashbooks as $cashbook)
                        <div class="border border-gray-200 rounded p-4 mb-3">
                            <div class="fw-bold text-gray-900">{{ $cashbook->code }} - {{ $cashbook->name }}</div>
                            <div class="text-muted fs-8">{{ optional($cashbook->businessLocation)->name }} | {{ optional($cashbook->cashAccount)->account_code }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No cashbooks configured yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Bank Accounts</div>
                </div>
                <div class="card-body">
                    @forelse ($bankAccounts as $bankAccount)
                        <div class="border border-gray-200 rounded p-4 mb-3">
                            <div class="fw-bold text-gray-900">{{ $bankAccount->account_code }} - {{ $bankAccount->bank_name }}</div>
                            <div class="text-muted fs-8">{{ $bankAccount->account_name }} | {{ $bankAccount->account_number }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No bank accounts configured yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Recent Cash Book</div>
                </div>
                <div class="card-body">
                    @forelse ($cashLedgerRows as $row)
                        <div class="border-bottom border-gray-200 py-3">
                            <div class="fw-semibold text-gray-900">{{ $row->voucher_no }}</div>
                            <div class="text-muted fs-8">{{ $row->posting_date }} | {{ number_format((float) $row->debit, 2) }} / {{ number_format((float) $row->credit, 2) }} {{ $currency }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No cash ledger activity yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Recent Bank Book</div>
                </div>
                <div class="card-body">
                    @forelse ($bankLedgerRows as $row)
                        <div class="border-bottom border-gray-200 py-3">
                            <div class="fw-semibold text-gray-900">{{ $row->voucher_no }}</div>
                            <div class="text-muted fs-8">{{ $row->posting_date }} | {{ number_format((float) $row->debit, 2) }} / {{ number_format((float) $row->credit, 2) }} {{ $currency }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No bank ledger activity yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Latest Statement Imports</div>
                </div>
                <div class="card-body">
                    @forelse ($statementImports as $statementImport)
                        <div class="border-bottom border-gray-200 py-3">
                            <div class="fw-semibold text-gray-900">{{ $statementImport->reference_no ?: 'Statement import #' . $statementImport->id }}</div>
                            <div class="text-muted fs-8">{{ optional($statementImport->imported_at)->format('Y-m-d H:i') }} | {{ $vasAccountingUtil->genericStatusLabel((string) $statementImport->status) }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No statement imports yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
