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
        'subtitle' => 'Month-end controls for soft lock, close blockers, reopen approval, and close-packet generation.',
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
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Soft locked</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $closingSummary['soft_locked'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Closed periods</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $closingSummary['closed'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Queued packets</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $closingSummary['packets'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header">
            <div class="card-title">Close packets</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Snapshot</th>
                            <th>Status</th>
                            <th>Generated</th>
                            <th class="text-end">Action</th>
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
                                        <a href="{{ route('vasaccounting.reports.snapshots.show', $packet->id) }}" class="btn btn-light-primary btn-sm">Open</a>
                                    @else
                                        <span class="text-muted fs-8">Waiting</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">No close packets have been queued yet.</td>
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
                <span>Close Control Board</span>
                <span class="text-muted fw-semibold fs-8 mt-1">Review blockers and run period-level close actions.</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Period</th>
                            <th>Status</th>
                            <th>{{ $vasAccountingUtil->metricLabel('draft_vouchers') }}</th>
                            <th>Failures</th>
                            <th>Pending depreciation</th>
                            <th>Unreconciled bank</th>
                            <th>Pending approvals</th>
                            <th>Checklist</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($periods as $period)
                            @php
                                $periodChecklists = $checklists[$period->id];
                                $completedChecklistCount = $periodChecklists->where('status', 'completed')->count();
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
                                <td>{{ $blockers[$period->id]['pending_approvals'] }}</td>
                                <td>{{ $completedChecklistCount }}/{{ $periodChecklists->count() }}</td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        @if ($period->status === 'open')
                                            <form method="POST" action="{{ route('vasaccounting.closing.soft_lock', $period->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-light-warning btn-sm">Soft lock</button>
                                            </form>
                                        @endif

                                        @if ($period->status !== 'closed')
                                            <form method="POST" action="{{ route('vasaccounting.closing.close', $period->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-light-primary btn-sm" {{ $completedChecklistCount !== $periodChecklists->count() ? 'disabled' : '' }}>Close period</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('vasaccounting.closing.packet', $period->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-light btn-sm">Queue packet</button>
                                        </form>
                                    </div>

                                    <form method="POST" action="{{ route('vasaccounting.closing.reopen', $period->id) }}" class="mt-3">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="reason" class="form-control" placeholder="Reason to reopen">
                                            <button type="submit" class="btn btn-light-danger">Reopen</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="9" class="bg-light-secondary">
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($periodChecklists as $item)
                                            <span class="badge {{ $item->status === 'completed' ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $item->title }}
                                            </span>
                                        @endforeach
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
