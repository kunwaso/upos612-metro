@forelse($attendance_by_shift as $data)
    <tr>
        <td>{{ $data['shift'] }}</td>
        <td>
            {{ $data['present'] }}
            @if(!empty($data['present_users']))
                <div class="text-muted fs-8 mt-1">{{ implode(', ', $data['present_users']) }}</div>
            @endif
        </td>
        <td>
            {{ $data['total'] - $data['present'] }}
            @if(!empty($data['absent_users']))
                <div class="text-muted fs-8 mt-1">{{ implode(', ', $data['absent_users']) }}</div>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="3" class="text-center">@lang('essentials::lang.no_data_found')</td>
    </tr>
@endforelse
