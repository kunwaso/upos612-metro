@extends('layouts.app')

@section('title', __('vasaccounting::lang.reports'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.reports'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-10 mb-8">
        @foreach ($hubSummary as $metric)
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

    @foreach (collect($reportDefinitions)->groupBy('group', true) as $group => $reports)
        <div class="card card-flush mb-8">
            <div class="card-header">
                <div class="card-title d-flex flex-column">
                    <span>{{ $group }}</span>
                    <span class="text-muted fw-semibold fs-8 mt-1">Báo cáo trực tiếp và ảnh chụp báo cáo phục vụ kiểm soát nhóm {{ mb_strtolower($group) }}.</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    @foreach ($reports as $reportKey => $report)
                        <div class="col-xl-4 col-md-6">
                            <div class="card card-bordered h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="fw-bold fs-4 text-gray-900 mb-2">{{ $vasAccountingUtil->reportKeyLabel((string) $reportKey) }}</div>
                                    <div class="text-muted fs-7 mb-6 flex-grow-1">{{ $report['description'] }}</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route($report['route']) }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open_live_report') }}</a>
                                        <form method="POST" action="{{ route('vasaccounting.reports.snapshots.store') }}">
                                            @csrf
                                            <input type="hidden" name="report_key" value="{{ $reportKey }}">
                                            <button type="submit" class="btn btn-light btn-sm">{{ $vasAccountingUtil->actionLabel('queue_snapshot') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span>Ảnh chụp báo cáo gần đây</span>
                <span class="text-muted fw-semibold fs-8 mt-1">Theo dõi trạng thái tạo báo cáo và mở các kết quả đã sẵn sàng.</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Ảnh chụp</th>
                            <th>Báo cáo</th>
                            <th>Trạng thái</th>
                            <th>Thời điểm tạo</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSnapshots as $snapshot)
                            <tr>
                                <td class="fw-semibold text-gray-900">{{ $snapshot->snapshot_name ?: $snapshot->report_key }}</td>
                                <td>{{ $vasAccountingUtil->reportKeyLabel((string) $snapshot->report_key) }}</td>
                                <td>
                                    <span class="badge {{ $snapshot->status === 'ready' ? 'badge-light-success' : ($snapshot->status === 'failed' ? 'badge-light-danger' : 'badge-light-warning') }}">
                                        {{ $vasAccountingUtil->genericStatusLabel((string) $snapshot->status) }}
                                    </span>
                                </td>
                                <td>{{ optional($snapshot->generated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td class="text-end">
                                    @if ($snapshot->status === 'ready')
                                        <a href="{{ route('vasaccounting.reports.snapshots.show', $snapshot->id) }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open') }}</a>
                                    @else
                                        <span class="text-muted fs-8">{{ $snapshot->error_message ?: 'Đang chờ hàng đợi xử lý.' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Chưa có ảnh chụp báo cáo nào được đưa vào hàng đợi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
