@extends('layouts.app')

@section('title', __('vasaccounting::lang.integrations'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.integrations'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-8 mb-8">
        @foreach ($overview['metrics'] as $metric)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 fw-semibold mb-2">{{ $metric['label'] }}</div>
                        <div class="text-gray-900 fw-bold fs-2">{{ $metric['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.views.integrations.cards.pending_runs') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $integrationStats['pending_runs'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.views.integrations.cards.run_failures') }}</div>
                    <div class="text-danger fw-bold fs-2">{{ $integrationStats['failed_runs'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.views.integrations.cards.webhook_errors') }}</div>
                    <div class="text-warning fw-bold fs-2">{{ $integrationStats['webhook_errors'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.views.integrations.cards.open_failures') }}</div>
                    <div class="text-danger fw-bold fs-2">{{ $integrationStats['open_failures'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.views.integrations.cards.snapshot_backlog') }}</div>
                    <div class="text-info fw-bold fs-2">{{ $integrationStats['snapshot_backlog'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-2">{{ __('vasaccounting::lang.views.integrations.cards.sync_candidates') }}</div>
                    <div class="text-success fw-bold fs-2">{{ $integrationStats['sync_candidates'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        @foreach ($overview['provider_groups'] as $group)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-gray-900 fw-bold fs-5 mb-1">{{ $group['label'] }}</div>
                        <div class="text-muted fs-7 mb-3">{{ __('vasaccounting::lang.views.integrations.provider_groups.adapters', ['count' => $group['count']]) }}</div>
                        <span class="badge badge-light-primary">{{ __('vasaccounting::lang.views.integrations.provider_groups.default', ['provider' => $group['default']]) }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.integrations.statement_import.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.integrations.statement_import.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}">
                        @csrf
                        <input type="hidden" name="run_type" value="bank_statement_import">
                        <input type="hidden" name="action" value="import_statement">
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('bank_statement_provider') }}</label>
                            <select name="provider" class="form-select form-select-solid" data-control="select2">
                                @foreach ($bankProviders as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}">{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.integrations.statement_import.bank_account') }}</label>
                            <select name="bank_account_id" class="form-select form-select-solid" data-control="select2">
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->account_code }} - {{ $bankAccount->bank_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.integrations.statement_import.reference') }}</label>
                            <input type="text" name="reference_no" class="form-control form-control-solid" placeholder="STM-20260328">
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.integrations.statement_import.statement_lines') }}</label>
                            <textarea name="statement_lines" rows="5" class="form-control form-control-solid" placeholder="2026-03-28|Incoming transfer|1500|4500"></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-light-primary">{{ __('vasaccounting::lang.views.integrations.statement_import.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.integrations.tax_export.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.integrations.tax_export.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}" class="mb-8">
                        @csrf
                        <input type="hidden" name="run_type" value="tax_export">
                        <input type="hidden" name="action" value="export_tax">
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('tax_export_provider') }}</label>
                            <select name="provider" class="form-select form-select-solid">
                                @foreach ($taxProviders as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}">{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.integrations.tax_export.export_type') }}</label>
                            <input type="text" name="export_type" class="form-control form-control-solid" value="vat_declaration">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-light-primary">{{ __('vasaccounting::lang.views.integrations.tax_export.submit') }}</button>
                        </div>
                    </form>

                    <div class="separator my-6"></div>

                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}">
                        @csrf
                        <input type="hidden" name="run_type" value="payroll_bridge">
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('payroll_bridge_provider') }}</label>
                            <select name="provider" class="form-select form-select-solid">
                                @foreach ($payrollProviders as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}">{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.integrations.payroll_bridge.mode') }}</label>
                            <select name="action" class="form-select form-select-solid">
                                <option value="bridge_group">{{ __('vasaccounting::lang.generic_statuses.bridge_group') }}</option>
                                <option value="bridge_payments">{{ __('vasaccounting::lang.generic_statuses.bridge_payments') }}</option>
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.integrations.payroll_bridge.payroll_group') }}</label>
                            <select name="payroll_group_id" class="form-select form-select-solid">
                                @foreach ($payrollGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-light-primary">{{ __('vasaccounting::lang.views.integrations.payroll_bridge.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.integrations.einvoice_sync.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.integrations.einvoice_sync.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}">
                        @csrf
                        <input type="hidden" name="run_type" value="einvoice_sync">
                        <input type="hidden" name="action" value="sync_status">
                        <div class="mb-5">
                            <label class="form-label">{{ $vasAccountingUtil->fieldLabel('einvoice_provider') }}</label>
                            <select name="provider" class="form-select form-select-solid">
                                @foreach ($einvoiceProviders as $providerKey => $providerLabel)
                                    <option value="{{ $providerKey }}">{{ $providerLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.integrations.einvoice_sync.document') }}</label>
                            <select name="einvoice_document_id" class="form-select form-select-solid" data-control="select2">
                                @foreach ($einvoiceDocuments as $document)
                                    <option value="{{ $document->id }}">{{ $document->document_no ?: __('vasaccounting::lang.views.integrations.einvoice_sync.document_fallback', ['id' => $document->id]) }} - {{ $vasAccountingUtil->genericStatusLabel((string) $document->status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-light-primary">{{ __('vasaccounting::lang.views.integrations.einvoice_sync.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.integrations.run_queue.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.integrations.run_queue.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-integrations-runs-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-integrations-runs-table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.integrations.run_queue.table.type') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.provider') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.action') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.requested') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.completed') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.error') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentRuns as $run)
                                    <tr>
                                        <td>{{ $vasAccountingUtil->genericStatusLabel((string) $run->run_type) }}</td>
                                        <td>{{ $run->provider ? $vasAccountingUtil->providerLabel((string) $run->provider) : '-' }}</td>
                                        <td>{{ $run->action }}</td>
                                        <td>
                                            <span class="badge {{ $run->status === 'failed' ? 'badge-light-danger' : ($run->status === 'completed' ? 'badge-light-success' : 'badge-light-warning') }}">
                                                {{ $vasAccountingUtil->genericStatusLabel((string) $run->status) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($run->created_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ optional($run->completed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td class="text-muted">{{ $run->error_message ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">{{ __('vasaccounting::lang.views.integrations.run_queue.empty') }}</td>
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
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.integrations.snapshots.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.integrations.snapshots.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-integrations-snapshots-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-integrations-snapshots-table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.snapshot') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.generated') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSnapshots as $snapshot)
                                    <tr>
                                        <td>{{ $snapshot->snapshot_name ?: $snapshot->report_key }}</td>
                                        <td>
                                            <span class="badge {{ $snapshot->status === 'ready' ? 'badge-light-success' : 'badge-light-warning' }}">
                                                {{ $vasAccountingUtil->genericStatusLabel((string) $snapshot->status) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($snapshot->generated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">{{ __('vasaccounting::lang.views.integrations.snapshots.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.integrations.failures.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.integrations.failures.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-integrations-failures-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-integrations-failures-table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.source') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.integrations.failures.table.failed_at') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.integrations.failures.table.message') }}</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($postingFailures as $failure)
                                    <tr>
                                        <td>{{ $failure->source_type }} #{{ $failure->source_id }}</td>
                                        <td>{{ optional($failure->failed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                        <td class="text-muted">{{ $failure->error_message }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('vasaccounting.integrations.failures.retry', $failure->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.integrations.failures.retry') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.integrations.failures.empty') }}</td>
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
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.integrations.webhooks.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.integrations.webhooks.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-integrations-webhooks-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-integrations-webhooks-table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.provider') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.event') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.received') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentWebhooks as $webhook)
                                    <tr>
                                        <td>{{ $vasAccountingUtil->providerLabel((string) $webhook->provider) }}</td>
                                        <td>{{ $webhook->event_key ?: ($webhook->external_reference ?: '-') }}</td>
                                        <td>
                                            <span class="badge {{ $webhook->status === 'failed' ? 'badge-light-danger' : 'badge-light-success' }}">
                                                {{ $vasAccountingUtil->genericStatusLabel((string) $webhook->status) }}
                                            </span>
                                        </td>
                                        <td>{{ optional($webhook->received_at)->format('Y-m-d H:i') ?: optional($webhook->created_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.integrations.webhooks.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const runsTable = window.VasWorkspace?.initLocalDataTable('#vas-integrations-runs-table', {
                order: [[4, 'desc']],
                pageLength: 10
            });
            if (runsTable) {
                $('#vas-integrations-runs-search').on('keyup', function () {
                    runsTable.search(this.value).draw();
                });
            }

            const snapshotsTable = window.VasWorkspace?.initLocalDataTable('#vas-integrations-snapshots-table', {
                order: [[2, 'desc']],
                pageLength: 10
            });
            if (snapshotsTable) {
                $('#vas-integrations-snapshots-search').on('keyup', function () {
                    snapshotsTable.search(this.value).draw();
                });
            }

            const failuresTable = window.VasWorkspace?.initLocalDataTable('#vas-integrations-failures-table', {
                order: [[1, 'desc']],
                pageLength: 10
            });
            if (failuresTable) {
                $('#vas-integrations-failures-search').on('keyup', function () {
                    failuresTable.search(this.value).draw();
                });
            }

            const webhooksTable = window.VasWorkspace?.initLocalDataTable('#vas-integrations-webhooks-table', {
                order: [[3, 'desc']],
                pageLength: 10
            });
            if (webhooksTable) {
                $('#vas-integrations-webhooks-search').on('keyup', function () {
                    webhooksTable.search(this.value).draw();
                });
            }
        });
    </script>
@endsection
