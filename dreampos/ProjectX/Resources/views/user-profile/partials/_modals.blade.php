<div class="modal fade" id="projectx_user_profile_edit_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('projectx.user_profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                <div class="modal-header">
                    <h2 class="fw-bold">{{ __('projectx::lang.update_profile') }}</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
                <div class="modal-body py-7">
                    <div class="row g-5">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('business.prefix') }}</label>
                            <input type="text" name="surname" value="{{ $targetUser->surname }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">{{ __('business.first_name') }}</label>
                            <input type="text" name="first_name" value="{{ $targetUser->first_name }}" class="form-control form-control-solid" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('business.last_name') }}</label>
                            <input type="text" name="last_name" value="{{ $targetUser->last_name }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('business.email') }}</label>
                            <input type="email" name="email" value="{{ $targetUser->email }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('business.language') }}</label>
                            <select name="language" class="form-select form-select-solid">
                                @foreach($languages as $language_key => $language_name)
                                    <option value="{{ $language_key }}" {{ $targetUser->language === $language_key ? 'selected' : '' }}>{{ $language_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang_v1.mobile_number') }}</label>
                            <input type="text" name="contact_number" value="{{ $targetUser->contact_number }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang_v1.family_contact_number') }}</label>
                            <input type="text" name="family_number" value="{{ $targetUser->family_number }}" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang_v1.current_address') }}</label>
                            <textarea name="current_address" rows="3" class="form-control form-control-solid">{{ $targetUser->current_address }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('lang_v1.permanent_address') }}</label>
                            <textarea name="permanent_address" rows="3" class="form-control form-control-solid">{{ $targetUser->permanent_address }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">{{ __('lang_v1.profile_photo') }}</label>
                            <input type="file" name="profile_photo" class="form-control form-control-solid" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="projectx_user_profile_password_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('projectx.user_profile.password.update') }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                <div class="modal-header">
                    <h2 class="fw-bold">{{ __('projectx::lang.change_password') }}</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
                <div class="modal-body py-7">
                    <div class="mb-5">
                        <label class="form-label">{{ __('projectx::lang.current_password') }}</label>
                        <input type="password" name="current_password" class="form-control form-control-solid" required>
                    </div>
                    <div class="mb-5">
                        <label class="form-label">{{ __('projectx::lang.new_password') }}</label>
                        <input type="password" name="new_password" class="form-control form-control-solid" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('projectx::lang.confirm_password') }}</label>
                        <input type="password" name="new_password_confirmation" class="form-control form-control-solid" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="projectx_user_profile_pin_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('projectx.user_profile.lock_screen_pin.update') }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                <div class="modal-header">
                    <h2 class="fw-bold">{{ __('projectx::lang.lock_screen_pin') }}</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
                <div class="modal-body py-7">
                    <div class="mb-5">
                        <label class="form-label">{{ __('projectx::lang.lock_screen_pin') }}</label>
                        <input type="password" name="lock_screen_pin" class="form-control form-control-solid" required maxlength="6">
                    </div>
                    <div>
                        <label class="form-label">{{ __('projectx::lang.confirm_password') }}</label>
                        <input type="password" name="lock_screen_pin_confirmation" class="form-control form-control-solid" required maxlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('projectx::lang.save_pin') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="projectx_user_profile_leave_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('/hrm/leave') }}" class="projectx-essentials-json-form">
                @csrf
                <input type="hidden" name="employees[]" value="{{ $targetUser->id }}">
                <div class="modal-header">
                    <h2 class="fw-bold">{{ __('projectx::lang.request_leave') }}</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
                <div class="modal-body py-7">
                    <div class="mb-5">
                        <label class="form-label">{{ __('projectx::lang.leave_type') }}</label>
                        <select name="essentials_leave_type_id" class="form-select form-select-solid" {{ empty($leave_types) ? 'disabled' : '' }}>
                            @foreach($leave_types as $leave_type)
                                <option value="{{ $leave_type['id'] }}">{{ $leave_type['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('projectx::lang.leave_start_date') }}</label>
                            <input type="date" name="start_date" class="form-control form-control-solid" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('projectx::lang.leave_end_date') }}</label>
                            <input type="date" name="end_date" class="form-control form-control-solid" required>
                        </div>
                    </div>
                    <div class="mt-5">
                        <label class="form-label">{{ __('projectx::lang.leave_reason') }}</label>
                        <textarea name="reason" rows="3" class="form-control form-control-solid"></textarea>
                    </div>
                    @if(empty($leave_types))
                        <div class="alert alert-warning mt-5 mb-0">
                            {{ __('projectx::lang.leave_unavailable') }}
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary" {{ empty($leave_types) ? 'disabled' : '' }}>{{ __('projectx::lang.request_leave') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="projectx_user_profile_task_edit_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('projectx.user_profile.tasks.update', ['task' => 0]) }}" id="projectx_user_profile_task_edit_form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                <div class="modal-header">
                    <h2 class="fw-bold">{{ __('general.edit') }}</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>
                <div class="modal-body py-7">
                    <div class="mb-5">
                        <label class="form-label">{{ __('projectx::lang.task_date') }}</label>
                        <input type="date" name="task_date" id="projectx_user_profile_task_edit_date" class="form-control form-control-solid" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('projectx::lang.task_title') }}</label>
                        <input type="text" name="title" id="projectx_user_profile_task_edit_title" class="form-control form-control-solid" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="projectx_user_profile_heatmap_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('projectx::lang.edit_cell_status') }}</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body py-7">
                <form
                    method="POST"
                    action="{{ route('projectx.user_profile.heatmap_overrides.upsert') }}"
                    id="projectx_user_profile_heatmap_override_form"
                    class="mb-7 projectx-heatmap-json-form"
                    data-projectx-success-action="close_modal"
                    data-projectx-modal-id="projectx_user_profile_heatmap_modal"
                >
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="user_id" id="projectx_user_profile_heatmap_user_id" value="{{ $targetUser->id }}">
                    <input type="hidden" name="work_date" id="projectx_user_profile_heatmap_work_date">
                    <input type="hidden" name="hour_slot" id="projectx_user_profile_heatmap_hour_slot">

                    <div class="mb-5">
                        <label class="form-label">{{ __('projectx::lang.status') }}</label>
                        <select class="form-select form-select-solid" name="status" id="projectx_user_profile_heatmap_status" required>
                            @foreach($heatmap_status_meta as $status_key => $status_data)
                                <option value="{{ $status_key }}">{{ $status_data['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="form-label">{{ __('projectx::lang.override_note') }}</label>
                        <textarea class="form-control form-control-solid" name="note" id="projectx_user_profile_heatmap_note" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('projectx::lang.save_override') }}</button>
                </form>

                <form
                    method="POST"
                    action="{{ route('projectx.user_profile.heatmap_overrides.destroy') }}"
                    id="projectx_user_profile_heatmap_clear_form"
                    class="projectx-heatmap-json-form"
                    data-projectx-success-action="close_modal"
                    data-projectx-modal-id="projectx_user_profile_heatmap_modal"
                >
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="user_id" id="projectx_user_profile_heatmap_clear_user_id" value="{{ $targetUser->id }}">
                    <input type="hidden" name="work_date" id="projectx_user_profile_heatmap_clear_work_date">
                    <input type="hidden" name="hour_slot" id="projectx_user_profile_heatmap_clear_hour_slot">
                    <button type="submit" class="btn btn-light-danger">{{ __('projectx::lang.clear_override') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
