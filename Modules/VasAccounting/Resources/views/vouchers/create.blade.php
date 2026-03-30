@extends('layouts.app')

@section('title', __('vasaccounting::lang.vouchers'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => 'Create Manual Voucher',
        'subtitle' => 'Use this journal-entry workspace for accruals, adjustments, and manual statutory postings.',
    ])

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span>Voucher Entry</span>
                        <span class="text-muted fw-semibold fs-8 mt-1">All field names and posting contracts are unchanged from the existing flow.</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.vouchers.store') }}">
                        @csrf
                        <div class="row g-5 mb-8">
                            <div class="col-md-3">
                                <label class="form-label required">Voucher type</label>
                                <input type="text" class="form-control form-control-solid" name="voucher_type" value="general_journal">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('vasaccounting::lang.module_area') }}</label>
                                <select class="form-select form-select-solid" name="module_area">
                                    <option value="accounting">Accounting</option>
                                    @foreach ($enterpriseDomains as $domainKey => $domainConfig)
                                        <option value="{{ $domainKey }}">{{ $domainConfig['title'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('vasaccounting::lang.document_type') }}</label>
                                <input type="text" class="form-control form-control-solid" name="document_type" value="general_journal">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Status</label>
                                <select class="form-select form-select-solid" name="status">
                                    @foreach ($documentStatuses as $statusKey => $statusLabel)
                                        @if (in_array($statusKey, ['draft', 'pending_approval', 'approved'], true))
                                            <option value="{{ $statusKey }}" {{ data_get($settings->approval_settings, 'default_manual_voucher_status', 'draft') === $statusKey ? 'selected' : '' }}>
                                                {{ $statusLabel }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-5 mb-8">
                            <div class="col-md-4">
                                <label class="form-label required">Posting date</label>
                                <input type="text" class="form-control form-control-solid" name="posting_date" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Document date</label>
                                <input type="text" class="form-control form-control-solid" name="document_date" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reference</label>
                                <input type="text" class="form-control form-control-solid" name="reference">
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="form-label">Description</label>
                            <textarea class="form-control form-control-solid" rows="3" name="description"></textarea>
                        </div>

                        <div class="table-responsive mb-5">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="vas-voucher-lines-table">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($i = 0; $i < 2; $i++)
                                        <tr>
                                            <td>
                                                <select class="form-select form-select-solid select2" data-control="select2" name="lines[{{ $i }}][account_id]">
                                                    <option value=""></option>
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" class="form-control form-control-solid" name="lines[{{ $i }}][description]"></td>
                                            <td><input type="text" class="form-control form-control-solid" name="lines[{{ $i }}][debit]" value="0"></td>
                                            <td><input type="text" class="form-control form-control-solid" name="lines[{{ $i }}][credit]" value="0"></td>
                                            <td class="text-end"><button type="button" class="btn btn-icon btn-light-danger btn-sm remove-line">&times;</button></td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-light-primary" id="add-voucher-line">Add line</button>
                            <button type="submit" class="btn btn-primary">Save voucher</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">Posting Notes</div>
                </div>
                <div class="card-body">
                    <div class="text-muted fs-7 mb-3">Keep debit and credit totals balanced before saving.</div>
                    <div class="text-muted fs-7 mb-3">Choose workflow status based on your manual approval policy.</div>
                    <div class="text-muted fs-7">Module area and document type feed reporting and integration workflows.</div>
                </div>
            </div>
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Quick Links</div>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    <a href="{{ route('vasaccounting.vouchers.index') }}" class="btn btn-light-primary btn-sm">Back to register</a>
                    <a href="{{ route('vasaccounting.chart.index') }}" class="btn btn-light btn-sm">Chart of accounts</a>
                    <a href="{{ route('vasaccounting.setup.index') }}" class="btn btn-light btn-sm">Setup center</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableBody = document.querySelector('#vas-voucher-lines-table tbody');
            const addButton = document.getElementById('add-voucher-line');

            function bindRemove(button) {
                button.addEventListener('click', function () {
                    if (tableBody.querySelectorAll('tr').length <= 2) {
                        return;
                    }

                    button.closest('tr').remove();
                });
            }

            document.querySelectorAll('.remove-line').forEach(bindRemove);

            addButton.addEventListener('click', function () {
                const index = tableBody.querySelectorAll('tr').length;
                const firstRow = tableBody.querySelector('tr');
                const newRow = firstRow.cloneNode(true);

                newRow.querySelectorAll('select, input').forEach(function (element) {
                    element.name = element.name.replace(/\[\d+\]/, '[' + index + ']');
                    if (element.tagName === 'SELECT') {
                        element.value = '';
                    } else {
                        element.value = element.name.includes('[description]') ? '' : '0';
                    }
                });

                tableBody.appendChild(newRow);
                bindRemove(newRow.querySelector('.remove-line'));
            });
        });
    </script>
@endsection
