@php
    $activityIconMap = [
        'fabric_created' => 'ki-message-text-2',
        'settings_updated' => 'ki-abstract-26',
        'image_added' => 'ki-disconnect',
        'image_removed' => 'ki-disconnect',
        'attachment_added' => 'ki-disconnect',
        'composition_updated' => 'ki-pencil',
        'pantone_updated' => 'ki-flag',
        'sale_added' => 'ki-flag',
    ];
@endphp

<div class="timeline">
    @forelse($logs as $log)
        @php
            $iconClass = $activityIconMap[$log->action_type] ?? 'ki-message-text-2';
            $userName = __('projectx::lang.system_user');

            if (! empty($log->user)) {
                $nameParts = array_filter([
                    $log->user->surname,
                    $log->user->first_name,
                    $log->user->last_name,
                ]);

                $fullName = trim(implode(' ', $nameParts));
                $userName = $fullName !== '' ? $fullName : $userName;
            }
        @endphp
        <div class="timeline-item">
            <div class="timeline-line w-40px"></div>
            <div class="timeline-icon symbol symbol-circle symbol-40px">
                <div class="symbol-label bg-light">
                    <i class="ki-duotone {{ $iconClass }} fs-2 text-gray-500"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                </div>
            </div>
            <div class="timeline-content {{ $loop->last ? 'mt-n1' : 'mb-10 mt-n1' }}">
                <div class="pe-3 mb-5">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="fs-5 fw-semibold mb-2">{{ $log->description }}</div>
                        @if($canDeleteActivity)
                            <form method="POST" action="{{ route('projectx.fabric_manager.activity.delete', ['fabric_id' => $fabric->id, 'log_id' => $log->id]) }}" onsubmit="return confirm('{{ __('projectx::lang.activity_delete') }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-sm btn-light" title="{{ __('projectx::lang.activity_delete') }}">
                                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="d-flex align-items-center mt-1 fs-6">
                        <div class="text-muted me-2 fs-7">{{ __('projectx::lang.activity_added_at') }} {{ optional($log->created_at)->format('M j, Y g:i A') }} {{ __('projectx::lang.activity_by') }}</div>
                        <span class="text-primary opacity-75-hover fs-7 fw-semibold">{{ $userName }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="timeline-item">
            <div class="timeline-line w-40px"></div>
            <div class="timeline-icon symbol symbol-circle symbol-40px">
                <div class="symbol-label bg-light">
                    <i class="ki-duotone ki-abstract-26 fs-2 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="timeline-content mt-n1">
                <div class="pe-3 mb-5">
                    <div class="fs-5 fw-semibold mb-2">{{ __('projectx::lang.activity_no_data_period') }}</div>
                </div>
            </div>
        </div>
    @endforelse
</div>
