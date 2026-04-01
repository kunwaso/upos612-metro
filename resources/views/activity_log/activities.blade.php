@if(!empty($activities))
<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-5">
        <thead>
            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                <th>@lang('lang_v1.date')</th>
                <th>@lang('messages.action')</th>
                <th>@lang('lang_v1.by')</th>
                <th>@lang('brand.note')</th>
            </tr>
        </thead>
        <tbody class="text-gray-700 fw-semibold">
            @forelse($activities as $activity)
                <tr>
                    <td>{{ format_datetime_value($activity->created_at) }}</td>
                    <td>{{ __('lang_v1.' . $activity->description) }}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="text-gray-900">{{ $activity->causer->user_full_name ?? '' }}</span>
                            @if(!empty($activity->getExtraProperty('from_api')))
                                <span class="badge badge-light-secondary mt-2 align-self-start">{{ $activity->getExtraProperty('from_api') }}</span>
                            @endif
                            @if(!empty($activity->getExtraProperty('is_automatic')))
                                <span class="badge badge-light-secondary mt-2 align-self-start">@lang('lang_v1.automatic')</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if(!empty($activity_type))
                            @if($activity_type == 'sell')
                                @include('sale_pos.partials.activity_row')
                            @elseif($activity_type == 'purchase')
                                @include('sale_pos.partials.activity_row')
                            @endif
                        @else
                            @php
                                $update_note = $activity->getExtraProperty('update_note');
                            @endphp
                            @if(!empty($update_note))
                                @if(!is_array($update_note))
                                    {{$update_note}}
                                @endif
                            @endif
                        @endif

                        @if(!empty($activity->getExtraProperty('email')))
                            <div class="text-gray-600 mt-2"><b>@lang('business.email'):</b> {{$activity->getExtraProperty('email')}}</div>
                        @endif

                        @if(!empty($activity->getExtraProperty('mobile')))
                            <div class="text-gray-600 mt-1"><b>@lang('business.mobile'):</b> {{$activity->getExtraProperty('mobile')}}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-gray-500 py-10">
                        @lang('purchase.no_records_found')
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endif
