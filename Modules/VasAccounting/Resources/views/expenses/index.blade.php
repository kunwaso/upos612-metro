@extends('layouts.app')

@section('title', __('vasaccounting::lang.views.expenses.page_title'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.views.expenses.page_title'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    @if ($closePeriod)
        <div class="alert alert-warning d-flex flex-column flex-sm-row align-items-start align-items-sm-center mb-8">
            <div class="me-4">
                <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.expenses.close_scope.title', ['period' => $vasAccountingUtil->localizedPeriodName($closePeriod->name)]) }}</div>
                <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.expenses.close_scope.subtitle', ['start' => optional($closePeriod->start_date)->format('Y-m-d'), 'end' => optional($closePeriod->end_date)->format('Y-m-d')]) }}</div>
            </div>
        </div>
    @endif

    @if ($workspaceFocus)
        <div class="card border border-warning mb-8">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
                <div>
                    <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.expenses.focus.title', ['focus' => __('vasaccounting::lang.views.expenses.focus.labels.' . $workspaceFocus)]) }}</div>
                    <div class="text-muted fs-7">
                        {{ __('vasaccounting::lang.views.expenses.focus.' . $workspaceFocus . '_subtitle') }}
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="#expense-register" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.expenses.focus.jump_register') }}</a>
                    <a href="{{ route('vasaccounting.expenses.index', array_filter(['location_id' => $selectedLocationId, 'period_id' => $closePeriod?->id])) }}" class="btn btn-light-danger btn-sm">{{ __('vasaccounting::lang.views.expenses.focus.clear') }}</a>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.expenses.cards.documents') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['documents']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.expenses.cards.documents_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.expenses.cards.open_workflow') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['open_workflow']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.expenses.cards.open_workflow_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.expenses.cards.posted_documents') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['posted_documents']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.expenses.cards.posted_documents_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100 border border-danger">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.expenses.cards.escalated_workflow') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['escalated_workflow']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.expenses.cards.escalated_workflow_help') }}</div>
                    <a
                        href="{{ route('vasaccounting.expenses.index', array_filter(['location_id' => $selectedLocationId, 'period_id' => $closePeriod?->id, 'focus' => 'escalated_approvals'])) }}"
                        class="btn btn-light-danger btn-sm mt-4"
                    >
                        {{ __('vasaccounting::lang.views.expenses.cards.review_escalated') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.expenses.cards.high_value_documents') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) $summary['high_value_documents']) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ __('vasaccounting::lang.views.expenses.cards.high_value_documents_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.expenses.cards.gross_amount') }}</span>
                    <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((float) $summary['gross_amount'], 2) }}</div>
                    <div class="text-muted fs-8 mt-1">{{ $currency }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        @foreach (['expense_claim', 'advance_request', 'advance_settlement', 'reimbursement_voucher'] as $type)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <span class="text-muted fw-semibold fs-7">{{ __('vasaccounting::lang.views.expenses.type_labels.' . $type) }}</span>
                        <div class="text-gray-900 fw-bold fs-2 mt-2">{{ number_format((int) ($documentTypeCounts[$type] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 g-xl-8 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100" id="expense-register">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.expenses.register.title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.expenses.register.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.expenses.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.document_type') }}</label>
                            <select name="document_type" class="form-select form-select-solid" required>
                                @foreach (['expense_claim', 'advance_request', 'advance_settlement', 'reimbursement_voucher'] as $type)
                                    <option value="{{ $type }}">{{ __('vasaccounting::lang.views.expenses.type_labels.' . $type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.document_no') }}</label>
                            <input type="text" name="document_no" class="form-control form-control-solid" placeholder="EXP-2026-001" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.reference') }}</label>
                            <input type="text" name="external_reference" class="form-control form-control-solid" placeholder="REQ-001">
                        </div>
                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.advance_request') }}</label>
                                <select name="advance_request_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.expenses.register.select_dimension') }}</option>
                                    @foreach ($advanceRequestOptions as $advanceRequest)
                                        <option value="{{ $advanceRequest->id }}">
                                            {{ $advanceRequest->document_no }} |
                                            {{ number_format((float) $advanceRequest->open_amount, 2) }} {{ $currency }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.expense_claim') }}</label>
                                <select name="expense_claim_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.expenses.register.select_dimension') }}</option>
                                    @foreach ($expenseClaimOptions as $expenseClaim)
                                        <option value="{{ $expenseClaim->id }}">
                                            {{ $expenseClaim->document_no }} |
                                            {{ number_format((float) $expenseClaim->open_amount, 2) }} {{ $currency }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.claimant') }}</label>
                            <select name="claimant_user_id" class="form-select form-select-solid" data-control="select2">
                                @foreach ($employeeOptions as $employeeId => $employeeLabel)
                                    <option value="{{ $employeeId }}">{{ $employeeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.shared.branch') }}</label>
                                <select name="business_location_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_branch') }}</option>
                                    @foreach ($locationOptions as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.department') }}</label>
                                <select name="department_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.expenses.register.select_dimension') }}</option>
                                    @foreach ($departmentOptions as $departmentId => $departmentLabel)
                                        <option value="{{ $departmentId }}">{{ $departmentLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.cost_center') }}</label>
                                <select name="cost_center_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.expenses.register.select_dimension') }}</option>
                                    @foreach ($costCenterOptions as $costCenterId => $costCenterLabel)
                                        <option value="{{ $costCenterId }}">{{ $costCenterLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.project') }}</label>
                                <select name="project_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.expenses.register.select_dimension') }}</option>
                                    @foreach ($projectOptions as $projectId => $projectLabel)
                                        <option value="{{ $projectId }}">{{ $projectLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.document_date') }}</label>
                                <input type="date" name="document_date" class="form-control form-control-solid" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.posting_date') }}</label>
                                <input type="date" name="posting_date" class="form-control form-control-solid" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.description') }}</label>
                            <input type="text" name="description" class="form-control form-control-solid" placeholder="{{ __('vasaccounting::lang.views.expenses.register.placeholders.description') }}" required>
                        </div>
                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.amount') }}</label>
                                <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-solid" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.tax_amount') }}</label>
                                <input type="number" name="tax_amount" step="0.01" min="0" class="form-control form-control-solid" value="0">
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.debit_account') }}</label>
                            <select name="debit_account_id" class="form-select form-select-solid" required data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.credit_account') }}</label>
                            <select name="credit_account_id" class="form-select form-select-solid" required data-control="select2">
                                <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                @foreach ($chartOptions as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.tax_account') }}</label>
                                <select name="tax_account_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.shared.select_account') }}</option>
                                    @foreach ($chartOptions as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('vasaccounting::lang.views.expenses.register.fields.tax_code') }}</label>
                                <select name="tax_code_id" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('vasaccounting::lang.views.expenses.register.select_dimension') }}</option>
                                    @foreach ($taxCodeOptions as $taxCode)
                                        <option value="{{ $taxCode->id }}">{{ $taxCode->code }} - {{ $taxCode->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.expenses.register.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.expenses.register.document_table_title') }}</span>
                        <span class="text-muted fs-7">{{ __('vasaccounting::lang.views.expenses.register.document_table_subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.expenses.table.document') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.expenses.table.claimant') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.expenses.table.type') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.expenses.table.amount') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.expenses.table.status') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.expenses.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expenseDocuments as $document)
                                    @php($claimant = data_get($document->meta, 'expense.claimant_name'))
                                    @php($approvalInstance = $document->approvalInstances->first())
                                    @php($pendingApprovalStep = $approvalInstance?->steps->firstWhere('status', 'pending'))
                                    @php($approvalSteps = collect($approvalInstance?->steps ?? []))
                                    @php($rejectedApprovalStep = $approvalSteps->firstWhere('status', 'rejected'))
                                    @php($approvalInsight = $approvalInsights[$document->id] ?? [])
                                    @php($expenseChain = (array) data_get($document->meta, 'expense_chain', []))
                                    <tr>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ $document->document_no }}</div>
                                            <div class="text-muted fs-8">{{ optional($document->document_date)->format('Y-m-d') }} | {{ $document->external_reference ?: __('vasaccounting::lang.views.expenses.table.no_reference') }}</div>
                                        </td>
                                        <td>
                                            <div class="text-gray-900 fw-semibold">{{ $claimant ?: __('vasaccounting::lang.views.expenses.table.unassigned_claimant') }}</div>
                                            <div class="text-muted fs-8">
                                                {{ data_get($departmentOptions, data_get($document->meta, 'expense.department_id')) ?: __('vasaccounting::lang.views.expenses.table.no_dimension') }}
                                                /
                                                {{ data_get($costCenterOptions, data_get($document->meta, 'expense.cost_center_id')) ?: __('vasaccounting::lang.views.expenses.table.no_dimension') }}
                                            </div>
                                        </td>
                                        <td>{{ __('vasaccounting::lang.views.expenses.type_labels.' . $document->document_type) }}</td>
                                        <td>{{ number_format((float) $document->gross_amount, 2) }} {{ $currency }}</td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge badge-light-primary">{{ $vasAccountingUtil->genericStatusLabel((string) $document->workflow_status) }}</span>
                                                <span class="badge badge-light-secondary">{{ $vasAccountingUtil->genericStatusLabel((string) $document->accounting_status) }}</span>
                                                @if ($approvalInstance)
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.approval_policy') }}:
                                                        {{ $approvalInstance->policy_code ?: __('vasaccounting::lang.views.expenses.table.no_policy') }}
                                                    </span>
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.approval_progress') }}:
                                                        {{ (int) ($approvalInstance->current_step_no ?: 1) }}/{{ max(1, $approvalSteps->count()) }}
                                                    </span>
                                                    @if (! empty($approvalInsight['threshold_label']))
                                                        <span class="text-muted fs-8">
                                                            {{ __('vasaccounting::lang.views.expenses.table.threshold') }}:
                                                            {{ $approvalInsight['threshold_label'] }}
                                                        </span>
                                                    @endif
                                                @endif
                                                @if ($pendingApprovalStep)
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.waiting_on') }}:
                                                        {{ $approvalInsight['current_step_role_label'] ?? ($pendingApprovalStep->approver_role ?: __('vasaccounting::lang.views.expenses.table.manual_reviewer')) }}
                                                    </span>
                                                @endif
                                                @if (! empty($approvalInsight['current_step_label']))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.current_step') }}:
                                                        {{ $approvalInsight['current_step_label'] }}
                                                    </span>
                                                @endif
                                                @if (! empty($approvalInsight['sla_label']) && ($approvalInsight['sla_state'] ?? 'not_applicable') !== 'not_applicable')
                                                    <span class="fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.approval_sla') }}:
                                                        <span class="badge {{ $approvalInsight['sla_badge_class'] ?? 'badge-light-secondary' }}">{{ $approvalInsight['sla_label'] }}</span>
                                                    </span>
                                                @endif
                                                @if (! empty($approvalInsight['escalation_message']))
                                                    <span class="text-danger fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.escalation') }}:
                                                        {{ $approvalInsight['escalation_message'] }}
                                                    </span>
                                                @endif
                                                @if (! empty($approvalInsight['last_escalated_at']))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.last_escalated') }}:
                                                        {{ \Carbon\Carbon::parse($approvalInsight['last_escalated_at'])->format('Y-m-d H:i') }}
                                                        @if (! empty($approvalInsight['escalation_count']))
                                                            ({{ __('vasaccounting::lang.views.expenses.table.escalation_count', ['count' => $approvalInsight['escalation_count']]) }})
                                                        @endif
                                                    </span>
                                                @endif
                                                @if (! empty($approvalInsight['last_escalation_reason']))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.last_escalation_reason') }}:
                                                        {{ $approvalInsight['last_escalation_reason'] }}
                                                    </span>
                                                @endif
                                                @if (! empty($approvalInsight['dispatch_status_label']))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.escalation_dispatch') }}:
                                                        {{ $approvalInsight['dispatch_status_label'] }}
                                                    </span>
                                                @endif
                                                @if (! empty($approvalInsight['dispatch_error']))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.escalation_dispatch_error') }}:
                                                        {{ $approvalInsight['dispatch_error'] }}
                                                    </span>
                                                @endif
                                                @if ($rejectedApprovalStep)
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.rejected_by') }}:
                                                        {{ $rejectedApprovalStep->approver_role ?: __('vasaccounting::lang.views.expenses.table.manual_reviewer') }}
                                                    </span>
                                                    @if ($rejectedApprovalStep->reason)
                                                        <span class="text-muted fs-8">
                                                            {{ __('vasaccounting::lang.views.expenses.table.rejection_reason') }}:
                                                            {{ $rejectedApprovalStep->reason }}
                                                        </span>
                                                    @endif
                                                @endif
                                                @if (! empty($expenseChain['linked_advance_document_no']))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.linked_advance') }}:
                                                        {{ $expenseChain['linked_advance_document_no'] }}
                                                    </span>
                                                @endif
                                                @if (! empty($expenseChain['linked_claim_document_no']))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.linked_claim') }}:
                                                        {{ $expenseChain['linked_claim_document_no'] }}
                                                    </span>
                                                @endif
                                                @if (array_key_exists('remaining_advance_amount', $expenseChain))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.remaining_advance') }}:
                                                        {{ number_format((float) $expenseChain['remaining_advance_amount'], 2) }} {{ $currency }}
                                                    </span>
                                                @endif
                                                @if (array_key_exists('outstanding_amount', $expenseChain))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.outstanding_amount') }}:
                                                        {{ number_format((float) $expenseChain['outstanding_amount'], 2) }} {{ $currency }}
                                                    </span>
                                                @endif
                                                @if (! empty($expenseChain['settlement_status']))
                                                    <span class="text-muted fs-8">
                                                        {{ __('vasaccounting::lang.views.expenses.table.settlement_status') }}:
                                                        {{ str_replace('_', ' ', (string) $expenseChain['settlement_status']) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                @if (in_array($document->workflow_status, ['draft', 'rejected'], true))
                                                    <form method="POST" action="{{ route('vasaccounting.expenses.submit', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-primary btn-sm">
                                                            {{ $document->workflow_status === 'rejected' ? __('vasaccounting::lang.views.expenses.actions.resubmit') : __('vasaccounting::lang.views.expenses.actions.submit') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                @if ($document->workflow_status === 'submitted')
                                                    <form method="POST" action="{{ route('vasaccounting.expenses.approve', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-success btn-sm">{{ __('vasaccounting::lang.views.expenses.actions.approve') }}</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('vasaccounting.expenses.reject', $document->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="">
                                                        <button
                                                            type="submit"
                                                            class="btn btn-light-danger btn-sm"
                                                            onclick="var reason = prompt('{{ __('vasaccounting::lang.views.expenses.actions.reject_prompt') }}'); if (!reason) { return false; } this.form.querySelector('input[name=&quot;reason&quot;]').value = reason;"
                                                        >
                                                            {{ __('vasaccounting::lang.views.expenses.actions.reject') }}
                                                        </button>
                                                    </form>
                                                    @if (($approvalInsight['sla_state'] ?? null) === 'overdue')
                                                        <form method="POST" action="{{ route('vasaccounting.expenses.escalate', $document->id) }}">
                                                            @csrf
                                                            <input type="hidden" name="reason" value="">
                                                            <button
                                                                type="submit"
                                                                class="btn btn-light-warning btn-sm"
                                                                onclick="var reason = prompt('{{ __('vasaccounting::lang.views.expenses.actions.escalate_prompt') }}'); if (!reason) { return false; } this.form.querySelector('input[name=&quot;reason&quot;]').value = reason;"
                                                            >
                                                                {{ !empty($approvalInsight['escalation_count']) ? __('vasaccounting::lang.views.expenses.actions.escalate_again') : __('vasaccounting::lang.views.expenses.actions.escalate') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if (($approvalInsight['dispatch_status'] ?? null) === 'failed')
                                                        <form method="POST" action="{{ route('vasaccounting.expenses.retry_escalation_dispatch', $document->id) }}">
                                                            @csrf
                                                            <input type="hidden" name="reason" value="">
                                                            <button
                                                                type="submit"
                                                                class="btn btn-light-warning btn-sm"
                                                                onclick="var reason = prompt('{{ __('vasaccounting::lang.views.expenses.actions.retry_dispatch_prompt') }}'); if (!reason) { return false; } this.form.querySelector('input[name=&quot;reason&quot;]').value = reason;"
                                                            >
                                                                {{ __('vasaccounting::lang.views.expenses.actions.retry_dispatch') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                                @if ($document->workflow_status === 'approved' && $document->accounting_status === 'ready_to_post')
                                                    <form method="POST" action="{{ route('vasaccounting.expenses.post', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-success btn-sm">{{ __('vasaccounting::lang.views.expenses.actions.post') }}</button>
                                                    </form>
                                                @endif
                                                @if ($document->workflow_status === 'posted')
                                                    <form method="POST" action="{{ route('vasaccounting.expenses.reverse', $document->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-light-danger btn-sm">{{ __('vasaccounting::lang.views.expenses.actions.reverse') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted">{{ __('vasaccounting::lang.views.expenses.table.empty') }}</td>
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
