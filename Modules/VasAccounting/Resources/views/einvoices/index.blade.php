@extends('layouts.app')

@section('title', __('vasaccounting::lang.einvoices'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.einvoices'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.einvoices.cards.documents') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $stats['documents']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.einvoices.cards.documents_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.einvoices.cards.ready_to_issue') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $stats['ready_to_issue']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.einvoices.cards.ready_to_issue_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.einvoices.cards.failed_rejected') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $stats['failed_or_rejected']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.einvoices.cards.failed_rejected_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.einvoices.cards.synced_today') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $stats['synced_today']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.einvoices.cards.synced_today_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.einvoices.documents.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.einvoices.documents.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-einvoices-documents-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-einvoices-documents-table">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.einvoices.documents.table.document_no') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.einvoices.documents.table.provider') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.voucher') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.einvoices.documents.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($documents as $document)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $document->document_no ?: __('vasaccounting::lang.views.einvoices.documents.pending_issue') }}</td>
                                        <td>{{ $vasAccountingUtil->providerLabel((string) $document->provider, 'einvoice_adapters') }}</td>
                                        <td>
                                            <span class="badge {{ in_array((string) $document->status, ['failed', 'rejected'], true) ? 'badge-light-danger' : (in_array((string) $document->status, ['issued', 'synced'], true) ? 'badge-light-success' : 'badge-light-primary') }}">
                                                {{ $vasAccountingUtil->genericStatusLabel((string) $document->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $document->voucher_id }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('vasaccounting.einvoices.sync', $document->id) }}" class="d-flex flex-column gap-2">
                                                @csrf
                                                <select name="provider" class="form-select form-select-sm">
                                                    @foreach ($providerOptions as $providerKey => $providerLabel)
                                                        <option value="{{ $providerKey }}" @selected($providerKey === $document->provider)>{{ $providerLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('vasaccounting::lang.views.einvoices.documents.notes_placeholder') }}">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.sync', $document->id) }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('sync') }}</button>
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.cancel', $document->id) }}" class="btn btn-light-warning btn-sm">{{ $vasAccountingUtil->actionLabel('cancel') }}</button>
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.correct', $document->id) }}" class="btn btn-light-info btn-sm">{{ $vasAccountingUtil->actionLabel('correct') }}</button>
                                                    <button type="submit" formaction="{{ route('vasaccounting.einvoices.replace', $document->id) }}" class="btn btn-light-success btn-sm">{{ $vasAccountingUtil->actionLabel('replace') }}</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.einvoices.documents.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.einvoices.issue_queue.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.einvoices.issue_queue.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-einvoices-issue-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-einvoices-issue-table">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.einvoices.issue_queue.table.voucher') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.einvoices.issue_queue.table.type') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.einvoices.issue_queue.table.issue') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentVouchers as $voucher)
                                    <tr>
                                        <td class="fw-semibold text-gray-900">{{ $voucher->voucher_no }}</td>
                                        <td>{{ $vasAccountingUtil->voucherTypeLabel((string) $voucher->voucher_type) }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('vasaccounting.einvoices.issue', $voucher->id) }}" class="d-flex gap-2">
                                                @csrf
                                                <select name="provider" class="form-select form-select-sm">
                                                    @foreach ($providerOptions as $providerKey => $providerLabel)
                                                        <option value="{{ $providerKey }}" @selected($providerKey === $defaultProvider)>{{ $providerLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('issue_einvoice') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">{{ __('vasaccounting::lang.views.einvoices.issue_queue.empty') }}</td>
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
                <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.einvoices.logs.title') }}</span>
                <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.einvoices.logs.subtitle') }}</span>
            </div>
        </div>
        <div class="card-body pt-0">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-einvoices-logs-search',
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-einvoices-logs-table">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.einvoices.logs.table.created_at') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.action') }}</th>
                            <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                            <th>{{ __('vasaccounting::lang.views.einvoices.logs.table.document') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLogs as $log)
                            <tr>
                                <td>{{ $log->created_at }}</td>
                                <td>{{ $vasAccountingUtil->actionLabel((string) $log->action) }}</td>
                                <td>
                                    <span class="badge {{ in_array((string) $log->status, ['failed', 'rejected'], true) ? 'badge-light-danger' : 'badge-light-primary' }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $log->status) }}
                                    </span>
                                </td>
                                <td>{{ $log->einvoice_document_id }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.einvoices.logs.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const invoiceDocumentsTable = window.VasWorkspace?.initLocalDataTable('#vas-einvoices-documents-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });
            if (invoiceDocumentsTable) {
                $('#vas-einvoices-documents-search').on('keyup', function () {
                    invoiceDocumentsTable.search(this.value).draw();
                });
            }

            const invoiceIssueTable = window.VasWorkspace?.initLocalDataTable('#vas-einvoices-issue-table', {
                order: [[0, 'asc']],
                pageLength: 10
            });
            if (invoiceIssueTable) {
                $('#vas-einvoices-issue-search').on('keyup', function () {
                    invoiceIssueTable.search(this.value).draw();
                });
            }

            const invoiceLogsTable = window.VasWorkspace?.initLocalDataTable('#vas-einvoices-logs-table', {
                order: [[0, 'desc']],
                pageLength: 10
            });
            if (invoiceLogsTable) {
                $('#vas-einvoices-logs-search').on('keyup', function () {
                    invoiceLogsTable.search(this.value).draw();
                });
            }
        });
    </script>
@endsection
