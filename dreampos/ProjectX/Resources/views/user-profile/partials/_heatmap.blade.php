<div class="card card-flush h-100">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column align-items-start">
            <h3 class="fw-bold text-gray-900 mb-1">{{ __('projectx::lang.attendance_report') }}</h3>
            <span class="text-muted fw-semibold fs-7">{{ __('projectx::lang.attendance_report_desc') }}</span>
        </div>
        <div class="card-toolbar d-flex flex-wrap align-items-center gap-3">
            <form method="GET" action="{{ route('projectx.user_profile.index') }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                <input type="hidden" name="task_date" value="{{ $task_date }}">
                <input type="date" name="date" value="{{ $selected_date }}" class="form-control form-control-sm form-control-solid" onchange="this.form.submit()">
            </form>
            @foreach($heatmap_status_meta as $status_key => $status_data)
                <span class="badge badge-light fs-8 fw-semibold d-flex align-items-center gap-2">
                    <span class="bullet bullet-dot {{ $status_data['legend_class'] }}"></span>
                    {{ $status_data['label'] }}
                </span>
            @endforeach
        </div>
    </div>
    <div class="card-body pt-2">
        @if(!$heatmap['is_available'])
            <div class="alert alert-warning d-flex align-items-center mb-0">
                <i class="ki-duotone ki-information fs-2 me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                <span>{{ __('projectx::lang.attendance_unavailable') }}</span>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7 gy-2">
                    <thead>
                        <tr class="text-muted fw-bold text-uppercase">
                            <th class="min-w-100px">{{ __('projectx::lang.date') }}</th>
                            @foreach($heatmap['hours'] as $hour)
                                <th class="text-center min-w-65px">{{ $hour['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-700">
                        @foreach($heatmap['days'] as $day)
                            <tr>
                                <td>
                                    <div class="fw-bold text-gray-900">{{ $day['day_label'] }}</div>
                                    <div class="text-muted fs-8">{{ $day['date_label'] }}</div>
                                </td>
                                @foreach($heatmap['hours'] as $hour)
                                    <td class="text-center">
                                        <button
                                            type="button"
                                            class="btn btn-sm p-0 h-35px w-55px {{ $heatmap_status_meta[$heatmap['cells'][$day['date']][$hour['hour_slot']]['status']]['cell_class'] ?? 'bg-light-secondary' }} projectx-heatmap-cell"
                                            data-work-date="{{ $day['date'] }}"
                                            data-hour-slot="{{ $hour['hour_slot'] }}"
                                            data-status="{{ $heatmap['cells'][$day['date']][$hour['hour_slot']]['status'] }}"
                                            data-note="{{ $heatmap['cells'][$day['date']][$hour['hour_slot']]['note'] ?? '' }}"
                                            data-user-id="{{ $targetUser->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#projectx_user_profile_heatmap_modal"
                                        >
                                            @if($heatmap['cells'][$day['date']][$hour['hour_slot']]['is_overridden'])
                                                <i class="ki-duotone ki-check-circle fs-5 text-white"><span class="path1"></span><span class="path2"></span></i>
                                            @endif
                                        </button>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
