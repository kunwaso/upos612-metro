@extends('layouts.app')

@section('title', __('vasaccounting::lang.integrations'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.integrations'),
        'subtitle' => 'Queued import/export runs, provider health, webhook logs, and posting failure recovery from one finance operations screen.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
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

    <div class="row g-5 g-xl-10 mb-8">
        @foreach ($overview['provider_groups'] as $group)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-gray-900 fw-bold fs-5 mb-1">{{ $group['label'] }}</div>
                        <div class="text-muted fs-7 mb-3">Adapters: {{ $group['count'] }}</div>
                        <div class="badge badge-light-primary">Default: {{ $group['default'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Bank statement import</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}">
                        @csrf
                        <input type="hidden" name="run_type" value="bank_statement_import">
                        <input type="hidden" name="action" value="import_statement">
                        <div class="mb-5">
                            <label class="form-label">Provider</label>
                            <select name="provider" class="form-select">
                                @foreach ($bankProviders as $provider)
                                    <option value="{{ $provider }}">{{ $provider }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Bank account</label>
                            <select name="bank_account_id" class="form-select">
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->account_code }} - {{ $bankAccount->bank_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Reference</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="STM-20260328">
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Statement lines</label>
                            <textarea name="statement_lines" rows="5" class="form-control" placeholder="2026-03-28|Incoming transfer|1500|4500"></textarea>
                        </div>
                        <button type="submit" class="btn btn-light-primary">Queue import</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Compliance and payroll</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}" class="mb-8">
                        @csrf
                        <input type="hidden" name="run_type" value="tax_export">
                        <input type="hidden" name="action" value="export_tax">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label">Tax provider</label>
                                <select name="provider" class="form-select">
                                    @foreach ($taxProviders as $provider)
                                        <option value="{{ $provider }}">{{ $provider }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Export type</label>
                                <input type="text" name="export_type" class="form-control" value="vat_declaration">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-light-primary mt-5">Queue tax export</button>
                    </form>

                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}">
                        @csrf
                        <input type="hidden" name="run_type" value="payroll_bridge">
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label">Provider</label>
                                <select name="provider" class="form-select">
                                    @foreach ($payrollProviders as $provider)
                                        <option value="{{ $provider }}">{{ $provider }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mode</label>
                                <select name="action" class="form-select">
                                    <option value="bridge_group">Accrual bridge</option>
                                    <option value="bridge_payments">Payment bridge</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payroll group</label>
                                <select name="payroll_group_id" class="form-select">
                                    @foreach ($payrollGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-light-primary mt-5">Queue payroll bridge</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">E-invoice sync</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.integrations.runs.store') }}">
                        @csrf
                        <input type="hidden" name="run_type" value="einvoice_sync">
                        <input type="hidden" name="action" value="sync_status">
                        <div class="mb-5">
                            <label class="form-label">Provider</label>
                            <select name="provider" class="form-select">
                                @foreach ($einvoiceProviders as $provider)
                                    <option value="{{ $provider }}">{{ $provider }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Document</label>
                            <select name="einvoice_document_id" class="form-select">
                                @foreach ($einvoiceDocuments as $document)
                                    <option value="{{ $document->id }}">{{ $document->document_no ?: ('Document #' . $document->id) }} - {{ $document->status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-light-primary">Queue sync</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Recent report snapshots</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Snapshot</th>
                                    <th>Status</th>
                                    <th>Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSnapshots as $snapshot)
                                    <tr>
                                        <td>{{ $snapshot->snapshot_name ?: $snapshot->report_key }}</td>
                                        <td>{{ ucfirst($snapshot->status) }}</td>
                                        <td>{{ optional($snapshot->generated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">No snapshots have been queued yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header">
            <div class="card-title">Integration runs</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Type</th>
                            <th>Provider</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Completed</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentRuns as $run)
                            <tr>
                                <td>{{ $run->run_type }}</td>
                                <td>{{ $run->provider ?: '-' }}</td>
                                <td>{{ $run->action }}</td>
                                <td>{{ ucfirst($run->status) }}</td>
                                <td>{{ optional($run->created_at)->format('Y-m-d H:i') }}</td>
                                <td>{{ optional($run->completed_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td class="text-muted">{{ $run->error_message ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">No integration runs have been queued yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">Posting failures</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Source</th>
                                    <th>Failed at</th>
                                    <th>Message</th>
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
                                                <button type="submit" class="btn btn-light-primary btn-sm">Queue replay</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">No unresolved posting failures remain.</td>
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
                    <div class="card-title">Webhook log</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Provider</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentWebhooks as $webhook)
                                    <tr>
                                        <td>{{ $webhook->provider }}</td>
                                        <td>{{ $webhook->event_key ?: ($webhook->external_reference ?: '-') }}</td>
                                        <td>{{ ucfirst($webhook->status) }}</td>
                                        <td>{{ optional($webhook->received_at)->format('Y-m-d H:i') ?: optional($webhook->created_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted">No webhooks have been received yet.</td>
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
