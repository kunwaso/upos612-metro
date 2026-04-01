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
                                <td colspan="10" class="bg-light-secondary">
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
                                                    <a href="{{ route('vasaccounting.cash_bank.index', ['period_id' => $period->id]) }}" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.treasury.open_workspace') }}</a>
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
                                                    <a href="{{ route('vasaccounting.cash_bank.index', ['period_id' => $period->id]) }}" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.closing.control_board.treasury.open_workspace') }}</a>
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
