<div class="card card-flush mb-7">
    <div class="card-body p-7">
        <div class="d-flex flex-column flex-xl-row align-items-xl-center gap-7">
            <div class="symbol symbol-125px symbol-lg-150px symbol-circle flex-shrink-0">
                <img src="{{ $profile['avatar_url'] }}" alt="{{ $profile['full_name'] }}" class="object-fit-cover" />
            </div>
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="text-gray-900 fw-bold mb-1">{{ $profile['full_name'] }}</h2>
                        <div class="text-muted fw-semibold">{{ $profile['role_label'] }} <span class="mx-2">&bull;</span> {{ $profile['staff_code'] }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#projectx_user_profile_password_modal">
                            {{ __('projectx::lang.change_password') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-light-dark" data-bs-toggle="modal" data-bs-target="#projectx_user_profile_pin_modal">
                            {{ __('projectx::lang.lock_screen_pin') }}
                        </button>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2 text-gray-700 fw-semibold fs-6 mb-5">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ki-duotone ki-phone fs-4 text-primary"><span class="path1"></span><span class="path2"></span></i>
                        <span>{{ $profile['phone'] }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="ki-duotone ki-sms fs-4 text-primary"><span class="path1"></span><span class="path2"></span></i>
                        <span>{{ $profile['email'] }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="ki-duotone ki-geolocation fs-4 text-primary"><span class="path1"></span><span class="path2"></span></i>
                        <span>{{ $profile['address'] }}</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <button type="button" class="btn btn-primary px-8" data-bs-toggle="modal" data-bs-target="#projectx_user_profile_edit_modal">
                        {{ __('projectx::lang.update_profile') }}
                    </button>
                    <button type="button" class="btn btn-light-dark px-8" data-bs-toggle="modal" data-bs-target="#projectx_user_profile_leave_modal">
                        {{ __('projectx::lang.request_leave') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
