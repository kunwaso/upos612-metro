<tr data-user_id="{{ $user->id }}">
    <td>{{ $user->user_full_name }}</td>
    <td>
        @if(empty($attendance->clock_in_time))
            <input type="text"
                name="attendance[{{ $user->id }}][clock_in_time]"
                class="form-control form-control-solid date_time_picker"
                placeholder="@lang('essentials::lang.clock_in_time')"
                required>
        @else
            {{ @format_datetime($attendance->clock_in_time) }}
            <div class="text-muted fs-8">
                (@lang('essentials::lang.clocked_in') - {{ \Carbon\Carbon::parse($attendance->clock_in_time)->diffForHumans(\Carbon\Carbon::now()) }})
            </div>
            <input type="hidden" name="attendance[{{ $user->id }}][id]" value="{{ $attendance->id }}">
        @endif
    </td>
    <td>
        <input type="text"
            name="attendance[{{ $user->id }}][clock_out_time]"
            class="form-control form-control-solid date_time_picker"
            placeholder="@lang('essentials::lang.clock_out_time')">
    </td>
    <td>
        <select name="attendance[{{ $user->id }}][essentials_shift_id]" class="form-select form-select-solid">
            <option value="">@lang('messages.please_select')</option>
            @foreach($shifts as $shift_id => $shift_name)
                <option value="{{ $shift_id }}" {{ !empty($attendance->essentials_shift_id) && (int) $attendance->essentials_shift_id === (int) $shift_id ? 'selected' : '' }}>
                    {{ $shift_name }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="text"
            name="attendance[{{ $user->id }}][ip_address]"
            class="form-control form-control-solid"
            value="{{ !empty($attendance->ip_address) ? $attendance->ip_address : '' }}"
            placeholder="@lang('essentials::lang.ip_address')">
    </td>
    <td>
        <textarea
            name="attendance[{{ $user->id }}][clock_in_note]"
            class="form-control form-control-solid"
            rows="2"
            placeholder="@lang('essentials::lang.clock_in_note')">{{ !empty($attendance->clock_in_note) ? $attendance->clock_in_note : '' }}</textarea>
    </td>
    <td>
        <textarea
            name="attendance[{{ $user->id }}][clock_out_note]"
            class="form-control form-control-solid"
            rows="2"
            placeholder="@lang('essentials::lang.clock_out_note')">{{ !empty($attendance->clock_out_note) ? $attendance->clock_out_note : '' }}</textarea>
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-light-danger remove_attendance_row">
            <i class="fa fa-times"></i>
        </button>
    </td>
</tr>
