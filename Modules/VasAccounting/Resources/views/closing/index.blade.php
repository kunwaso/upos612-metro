@extends('layouts.app')

@section('title', __('vasaccounting::lang.closing'))

@section('content')
    @php
        $periodCollection = collect($periods);
        $closingSummary = [
            'open' => $periodCollection->where('status', 'open')->count(),
            'soft_locked' => $periodCollection->where('status', 'soft_locked')->count(),
            'closed' => $periodCollection->where('status', 'closed')->count(),
            'packets' => $recentPackets->count(),
        ];
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.closing'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ $vasAccountingUtil->metricLabel('open_periods') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $closingSummary['open'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.closing.cards.soft_locked') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $closingSummary['soft_locked'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.closing.cards.closed_periods') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $closingSummary['closed'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.closing.cards.queued_packets') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $closingSummary['packets'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header">
            <div class="card-title">{{ __('vasaccounting::lang.views.closing.packets.title') }}</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.closing.packets.table.snapshot') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.packets.table.status') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.packets.table.generated') }}</th>
                            <th class="text-end">{{ __('vasaccounting::lang.views.closing.packets.table.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentPackets as $packet)
                            <tr>
                                <td>{{ $packet->snapshot_name ?: $packet->report_key }}</td>
                                <td>{{ $vasAccountingUtil->genericStatusLabel((string) $packet->status) }}</td>
                                <td>{{ optional($packet->generated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td class="text-end">
                                    @if ($packet->status === 'ready')
                                        <a href="{{ route('vasaccounting.reports.snapshots.show', $packet->id) }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open') }}</a>
                                    @else
                                        <span class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.packets.waiting') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.closing.packets.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span>{{ __('vasaccounting::lang.views.closing.control_board.title') }}</span>
                <span class="text-muted fw-semibold fs-8 mt-1">{{ __('vasaccounting::lang.views.closing.control_board.subtitle') }}</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.period') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.status') }}</th>
                            <th>{{ $vasAccountingUtil->metricLabel('draft_vouchers') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.failures') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.pending_depreciation') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.unreconciled_bank') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.pending_treasury_docs') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.procurement_blockers') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.expense_blockers') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.pending_approvals') }}</th>
                            <th>{{ __('vasaccounting::lang.views.closing.control_board.table.checklist') }}</th>
                            <th class="text-end">{{ __('vasaccounting::lang.views.closing.control_board.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($periods as $period)
                            @php
                                $periodChecklists = $checklists[$period->id];
                                $completedChecklistCount = $periodChecklists->where('status', 'completed')->count();
                                $periodTreasury = $treasuryInsights[$period->id] ?? ['pending_documents' => collect(), 'exceptions' => collect()];
                                $periodProcurement = $procurementInsights[$period->id] ?? ['pending_documents' => collect(), 'receiving_documents' => collect(), 'matching_documents' => collect()];
                                $periodExpense = $expenseInsights[$period->id] ?? ['pending_documents' => collect(), 'outstanding_documents' => collect(), 'escalated_approvals' => collect()];
                            @endphp
                            <tr>
                                <td>{{ $vasAccountingUtil->localizedPeriodName($period->name) }}</td>
                                <td>
                                    <span class="badge {{ $period->status === 'closed' ? 'badge-light-danger' : ($period->status === 'soft_locked' ? 'badge-light-warning' : 'badge-light-success') }}">
                                        {{ $vasAccountingUtil->periodStatusLabel((string) $period->status) }}
                                    </span>
                                </td>
                                <td>{{ $blockers[$period->id]['draft_vouchers'] }}</td>
                                <td>{{ $blockers[$period->id]['posting_failures'] }}</td>
                                <td>{{ $blockers[$period->id]['pending_depreciation'] }}</td>
                                <td>{{ $blockers[$period->id]['unreconciled_bank_lines'] }}</td>
                                <td>{{ $blockers[$period->id]['pending_treasury_documents'] }}</td>
                                <td>{{ $blockers[$period->id]['pending_procurement_documents'] + $blockers[$period->id]['receiving_procurement_documents'] + $blockers[$period->id]['matching_procurement_documents'] }}</td>
                                <td>{{ $blockers[$period->id]['pending_expense_documents'] + $blockers[$period->id]['outstanding_expense_documents'] + $blockers[$period->id]['escalated_expense_approvals'] }}</td>
                                <td>{{ $blockers[$period->id]['pending_approvals'] }}</td>
                                <td>{{ $completedChecklistCount }}/{{ $periodChecklists->count() }}</td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        @if ($period->status === 'open')
                                            <form method="POST" action="{{ route('vasaccounting.closing.soft_lock', $period->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-light-warning btn-sm">{{ $vasAccountingUtil->actionLabel('soft_lock') }}</button>
                                            </form>
                                        @endif

                                        @if ($period->status !== 'closed')
                                            <form method="POST" action="{{ route('vasaccounting.closing.close', $period->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-light-primary btn-sm" {{ $completedChecklistCount !== $periodChecklists->count() ? 'disabled' : '' }}>{{ __('vasaccounting::lang.views.closing.control_board.actions.close_period') }}</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('vasaccounting.closing.packet', $period->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.actions.queue_packet') }}</button>
                                        </form>
                                    </div>

                                    <form method="POST" action="{{ route('vasaccounting.closing.reopen', $period->id) }}" class="mt-3">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="reason" class="form-control" placeholder="{{ __('vasaccounting::lang.views.closing.control_board.actions.reopen_reason') }}">
                                            <button type="submit" class="btn btn-light-danger">{{ $vasAccountingUtil->actionLabel('reopen') }}</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="12" class="bg-light-secondary">
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($periodChecklists as $item)
                                            <span class="badge {{ $item->status === 'completed' ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $item->title }}
                                            </span>
                                        @endforeach
                                    </div>

                                    <div class="row g-5 mt-1">
                                        <div class="col-xl-6">
                                            <div class="border border-gray-300 rounded p-4 bg-white">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.closing.control_board.treasury.pending_title') }}</div>
                                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.treasury.pending_subtitle') }}</div>
                                                    </div>
                                                    <a href="{{ route('vasaccounting.cash_bank.index', ['period_id' => $period->id, 'focus' => 'pending_documents']) }}#native-treasury-documents" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.treasury.review_pending') }}</a>
                                                </div>

                                                @forelse ($periodTreasury['pending_documents'] as $document)
                                                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom border-gray-200">
                                                        <div>
                                                            <div class="fw-semibold text-gray-900">{{ $document->document_no ?: ('#' . $document->id) }}</div>
                                                            <div class="text-muted fs-8">{{ \Illuminate\Support\Str::headline((string) $document->document_type) }} | {{ optional($document->posting_date ?: $document->document_date)->format('Y-m-d') ?: '-' }}</div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-gray-900">{{ number_format((float) $document->gross_amount, 2) }} {{ $document->currency_code }}</div>
                                                            <div class="d-flex gap-2 justify-content-end mt-1">
                                                                <span class="badge badge-light-warning">{{ $vasAccountingUtil->genericStatusLabel((string) $document->workflow_status) }}</span>
                                                                <span class="badge badge-light-secondary">{{ $vasAccountingUtil->genericStatusLabel((string) $document->accounting_status) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.treasury.pending_empty') }}</div>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="col-xl-6">
                                            <div class="border border-gray-300 rounded p-4 bg-white">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.closing.control_board.treasury.exceptions_title') }}</div>
                                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.treasury.exceptions_subtitle') }}</div>
                                                    </div>
                                                    <a href="{{ route('vasaccounting.cash_bank.index', ['period_id' => $period->id, 'focus' => 'treasury_exceptions', 'exception_status' => 'open,suggested']) }}#treasury-exception-queue" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.treasury.review_exceptions') }}</a>
                                                </div>

                                                @forelse ($periodTreasury['exceptions'] as $exception)
                                                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom border-gray-200">
                                                        <div>
                                                            <div class="fw-semibold text-gray-900">{{ optional($exception->statementLine)->description ?: __('vasaccounting::lang.views.closing.control_board.treasury.statement_line_fallback') }}</div>
                                                            <div class="text-muted fs-8">{{ optional(optional($exception->statementLine)->transaction_date)->format('Y-m-d') ?: '-' }} | {{ strtoupper((string) $exception->status) }}</div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-gray-900">{{ number_format((float) optional($exception->statementLine)->amount, 2) }}</div>
                                                            <div class="text-muted fs-8">
                                                                {{ $exception->recommendedDocument?->document_no
                                                                    ? __('vasaccounting::lang.views.closing.control_board.treasury.recommended_document', ['document' => $exception->recommendedDocument->document_no])
                                                                    : __('vasaccounting::lang.views.closing.control_board.treasury.no_recommendation') }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.treasury.exceptions_empty') }}</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-5 mt-1">
                                        <div class="col-xl-4">
                                            <div class="border border-gray-300 rounded p-4 bg-white">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.closing.control_board.procurement.pending_title') }}</div>
                                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.procurement.pending_subtitle') }}</div>
                                                    </div>
                                                    <a href="{{ route('vasaccounting.procurement.index', ['period_id' => $period->id, 'focus' => 'pending_documents']) }}#procurement-register" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.procurement.review_pending') }}</a>
                                                </div>

                                                @forelse ($periodProcurement['pending_documents'] as $document)
                                                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom border-gray-200">
                                                        <div>
                                                            <div class="fw-semibold text-gray-900">{{ $document->document_no ?: ('#' . $document->id) }}</div>
                                                            <div class="text-muted fs-8">
                                                                {{ \Illuminate\Support\Str::headline((string) $document->document_type) }}
                                                                |
                                                                {{ optional($document->posting_date ?: $document->document_date)->format('Y-m-d') ?: '-' }}
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-gray-900">{{ number_format((float) $document->gross_amount, 2) }} {{ $document->currency_code }}</div>
                                                            <span class="badge badge-light-warning mt-1">{{ $vasAccountingUtil->genericStatusLabel((string) $document->workflow_status) }}</span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.procurement.pending_empty') }}</div>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="col-xl-4">
                                            <div class="border border-gray-300 rounded p-4 bg-white">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.closing.control_board.procurement.receiving_title') }}</div>
                                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.procurement.receiving_subtitle') }}</div>
                                                    </div>
                                                    <a href="{{ route('vasaccounting.procurement.index', ['period_id' => $period->id, 'focus' => 'receiving_queue']) }}#procurement-register" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.procurement.review_receiving') }}</a>
                                                </div>

                                                @forelse ($periodProcurement['receiving_documents'] as $document)
                                                    @php($childReceiptCount = $document->childLinks->pluck('childDocument')->filter(fn ($child) => $child && $child->document_type === 'goods_receipt')->count())
                                                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom border-gray-200">
                                                        <div>
                                                            <div class="fw-semibold text-gray-900">{{ $document->document_no ?: ('#' . $document->id) }}</div>
                                                            <div class="text-muted fs-8">
                                                                {{ \Illuminate\Support\Str::headline((string) $document->workflow_status) }}
                                                                |
                                                                {{ optional($document->document_date)->format('Y-m-d') ?: '-' }}
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-gray-900">{{ $childReceiptCount }}</div>
                                                            <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.procurement.receipts_recorded') }}</div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.procurement.receiving_empty') }}</div>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="col-xl-4">
                                            <div class="border border-gray-300 rounded p-4 bg-white">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.closing.control_board.procurement.matching_title') }}</div>
                                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.procurement.matching_subtitle') }}</div>
                                                    </div>
                                                    <a href="{{ route('vasaccounting.procurement.index', ['period_id' => $period->id, 'focus' => 'pending_matching']) }}#procurement-register" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.procurement.review_matching') }}</a>
                                                </div>

                                                @forelse ($periodProcurement['matching_documents'] as $document)
                                                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom border-gray-200">
                                                        <div>
                                                            <div class="fw-semibold text-gray-900">{{ $document->document_no ?: ('#' . $document->id) }}</div>
                                                            <div class="text-muted fs-8">
                                                                {{ data_get($document->meta, 'matching.latest_status')
                                                                    ? strtoupper((string) data_get($document->meta, 'matching.latest_status'))
                                                                    : __('vasaccounting::lang.views.closing.control_board.procurement.awaiting_match') }}
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-gray-900">
                                                                {{ (int) data_get($document->meta, 'matching.blocking_exception_count', 0) }}
                                                                /
                                                                {{ (int) data_get($document->meta, 'matching.warning_count', 0) }}
                                                            </div>
                                                            <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.procurement.match_exception_counts') }}</div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.procurement.matching_empty') }}</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-5 mt-1">
                                        <div class="col-xl-4">
                                            <div class="border border-gray-300 rounded p-4 bg-white">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.closing.control_board.expenses.pending_title') }}</div>
                                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.expenses.pending_subtitle') }}</div>
                                                    </div>
                                                    <a href="{{ route('vasaccounting.expenses.index', ['period_id' => $period->id, 'focus' => 'pending_documents']) }}#expense-register" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.expenses.review_pending') }}</a>
                                                </div>

                                                @forelse ($periodExpense['pending_documents'] as $document)
                                                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom border-gray-200">
                                                        <div>
                                                            <div class="fw-semibold text-gray-900">{{ $document->document_no ?: ('#' . $document->id) }}</div>
                                                            <div class="text-muted fs-8">{{ \Illuminate\Support\Str::headline((string) $document->document_type) }} | {{ optional($document->posting_date ?: $document->document_date)->format('Y-m-d') ?: '-' }}</div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-gray-900">{{ number_format((float) $document->gross_amount, 2) }} {{ $document->currency_code }}</div>
                                                            <div class="d-flex gap-2 justify-content-end mt-1">
                                                                <span class="badge badge-light-warning">{{ $vasAccountingUtil->genericStatusLabel((string) $document->workflow_status) }}</span>
                                                                <span class="badge badge-light-secondary">{{ $vasAccountingUtil->genericStatusLabel((string) $document->accounting_status) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.expenses.pending_empty') }}</div>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="col-xl-4">
                                            <div class="border border-gray-300 rounded p-4 bg-white">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.closing.control_board.expenses.outstanding_title') }}</div>
                                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.expenses.outstanding_subtitle') }}</div>
                                                    </div>
                                                    <a href="{{ route('vasaccounting.expenses.index', ['period_id' => $period->id, 'focus' => 'outstanding_balances']) }}#expense-register" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.expenses.review_outstanding') }}</a>
                                                </div>

                                                @forelse ($periodExpense['outstanding_documents'] as $document)
                                                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom border-gray-200">
                                                        <div>
                                                            <div class="fw-semibold text-gray-900">{{ $document->document_no ?: ('#' . $document->id) }}</div>
                                                            <div class="text-muted fs-8">
                                                                {{ \Illuminate\Support\Str::headline((string) $document->document_type) }}
                                                                |
                                                                {{ data_get($document->meta, 'expense.claimant_name') ?: __('vasaccounting::lang.views.closing.control_board.expenses.unassigned_claimant') }}
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-gray-900">{{ number_format((float) $document->open_amount, 2) }} {{ $document->currency_code }}</div>
                                                            <div class="text-muted fs-8">{{ strtoupper((string) data_get($document->meta, 'expense_chain.settlement_status', 'open')) }}</div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.expenses.outstanding_empty') }}</div>
                                                @endforelse
                                            </div>
                                        </div>
                                        <div class="col-xl-4">
                                            <div class="border border-gray-300 rounded p-4 bg-white">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <div class="fw-bold text-gray-900">{{ __('vasaccounting::lang.views.closing.control_board.expenses.escalated_title') }}</div>
                                                        <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.expenses.escalated_subtitle') }}</div>
                                                    </div>
                                                    <a href="{{ route('vasaccounting.expenses.index', ['period_id' => $period->id, 'focus' => 'escalated_approvals']) }}#expense-register" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.expenses.review_escalated') }}</a>
                                                </div>

                                                @forelse ($periodExpense['escalated_approvals'] as $document)
                                                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom border-gray-200">
                                                        <div>
                                                            <div class="fw-semibold text-gray-900">{{ $document->document_no ?: ('#' . $document->id) }}</div>
                                                            <div class="text-muted fs-8">
                                                                {{ \Illuminate\Support\Str::headline((string) $document->document_type) }}
                                                                |
                                                                {{ data_get($document, 'approval_close_insight.current_step_role_label')
                                                                    ?: data_get($document, 'approval_close_insight.current_step_label')
                                                                    ?: __('vasaccounting::lang.views.closing.control_board.expenses.pending_reviewer') }}
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fw-bold text-gray-900">{{ data_get($document, 'approval_close_insight.sla_label', __('vasaccounting::lang.views.closing.control_board.expenses.no_sla')) }}</div>
                                                            <div class="text-muted fs-8">{{ data_get($document, 'approval_close_insight.escalation_message', __('vasaccounting::lang.views.closing.control_board.expenses.no_escalation_path')) }}</div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-muted fs-8">{{ __('vasaccounting::lang.views.closing.control_board.expenses.escalated_empty') }}</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
