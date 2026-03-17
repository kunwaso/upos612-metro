<div class="card card-flush">
    <div class="card-body">
        <h4 class="fw-bold mb-4">{{ $user->user_full_name }}</h4>
        @foreach($leave_type_summary_rows as $summary_row)
            <div class="mb-3">
                <div class="fw-semibold">{{ $summary_row['leave_type']->leave_type }}</div>
                <div class="text-muted fs-7">
                    @foreach($summary_row['status_counts'] as $status_count)
                        {{ $status_count['name'] }}: {{ $status_count['count'] }}
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
