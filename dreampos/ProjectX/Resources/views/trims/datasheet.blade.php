@extends('projectx::layouts.main')

@section('title', __('projectx::lang.trim_datasheet'))

@section('content')
    @include('projectx::trims._trim_header', ['trim' => $trim ?? null, 'currency' => $currency ?? null, 'activeTab' => 'datasheet'])

    @if(session('status'))
        <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show mb-5" role="alert">
            {{ session('status.msg') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('projectx::lang.close') }}"></button>
        </div>
    @endif

    <div class="row g-5 g-xl-8">
        <div class="col-12">
            <div class="card card-flush">
                <div class="card-header align-items-center">
                    <div class="card-title flex-column">
                        <h3 class="fw-bold mb-1">{{ __('projectx::lang.trim_datasheet') }}</h3>
                        <span class="text-muted fs-7">{{ __('projectx::lang.trim_datasheet_hint') }}</span>
                    </div>
                    <div class="card-toolbar d-flex flex-wrap gap-3">
                        <a
                            href="{{ Route::has('projectx.trim_manager.datasheet.pdf') && !empty($trim->id) ? route('projectx.trim_manager.datasheet.pdf', ['id' => $trim->id]) : '#' }}"
                            class="btn btn-primary btn-sm"
                        >
                            <i class="ki-duotone ki-file-down fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('projectx::lang.download_pdf') }}
                        </a>
                        @can('projectx.trim.edit')
                            <a href="#projectx_share_settings_card" class="btn btn-light-primary btn-sm">
                                <i class="ki-duotone ki-share fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                {{ __('projectx::lang.share_link') }}
                            </a>
                            @if(filled(data_get($shareSettings ?? [], 'share_url')))
                                <a href="{{ data_get($shareSettings ?? [], 'share_url') }}" target="_blank" class="btn btn-light btn-sm">
                                    <i class="ki-duotone ki-eye fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                    {{ __('projectx::lang.open_public_link') }}
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.current_status') }}:</span>
                        <span class="badge {{ $trim->badge_class ?? 'badge-light-secondary' }}">
                            {{ $trim->status_label ?? ucfirst(str_replace('_', ' ', (string) ($trim->status ?? 'draft'))) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @can('projectx.trim.edit')
            <div class="col-12" id="projectx_share_settings_card">
                <div class="accordion" id="kt_accordion_trim_share_settings">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="kt_accordion_trim_share_settings_header_1">
                            <button class="accordion-button fs-4 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#kt_accordion_trim_share_settings_body_1" aria-expanded="true" aria-controls="kt_accordion_trim_share_settings_body_1">
                                <i class="ki-duotone ki-lock-3 fs-2 me-3 text-indigo-600"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                {{ __('projectx::lang.share_settings') }}
                            </button>
                        </h2>
                        <div id="kt_accordion_trim_share_settings_body_1" class="accordion-collapse collapse show" aria-labelledby="kt_accordion_trim_share_settings_header_1" data-bs-parent="#kt_accordion_trim_share_settings">
                            <div class="accordion-body">
                                <p class="text-muted fs-7 mb-5">{{ __('projectx::lang.share_settings_hint') }}</p>

                                <form method="POST" action="{{ Route::has('projectx.trim_manager.share_settings.update') && !empty($trim->id) ? route('projectx.trim_manager.share_settings.update', ['id' => $trim->id]) : '#' }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="redirect_tab" value="datasheet" />

                                <div class="row mb-8">
                                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.enable_share_link') }}</label>
                                    <div class="col-lg-9">
                                        <div class="form-check form-switch form-check-custom form-check-solid">
                                            <input type="hidden" name="share_enabled" value="0">
                                            <input class="form-check-input" type="checkbox" name="share_enabled" value="1" id="trim_share_enabled" {{ old('share_enabled', data_get($shareSettings ?? [], 'share_enabled')) ? 'checked' : '' }} />
                                            <label class="form-check-label fw-semibold text-gray-700" for="trim_share_enabled">{{ __('projectx::lang.enable_share_link_description') }}</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-8">
                                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.share_url') }}</label>
                                    <div class="col-lg-9">
                                        @if(filled(data_get($shareSettings ?? [], 'share_url')))
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-solid" value="{{ data_get($shareSettings ?? [], 'share_url') }}" readonly id="projectx_trim_share_url_input" />
                                                <button class="btn btn-light-primary" type="button" data-copy-target="#projectx_trim_share_url_input">{{ __('projectx::lang.copy_link') }}</button>
                                            </div>
                                            <div class="form-text">{{ __('projectx::lang.share_url_note') }}</div>
                                        @else
                                            <div class="text-muted fs-7">{{ __('projectx::lang.share_url_not_available') }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row mb-8">
                                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.password_protect') }}</label>
                                    <div class="col-lg-9">
                                        <input type="password" name="share_password" class="form-control form-control-solid" autocomplete="new-password" placeholder="{{ __('projectx::lang.password_protect_placeholder') }}" />
                                        <div class="form-text">{{ __('projectx::lang.password_protect_hint') }}</div>
                                        <div class="form-check form-check-custom form-check-solid mt-4">
                                            <input class="form-check-input" type="checkbox" value="1" id="trim_clear_share_password" name="clear_share_password" {{ old('clear_share_password') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="trim_clear_share_password">{{ __('projectx::lang.clear_share_password') }}</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-8">
                                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.share_rate_limit_per_day') }}</label>
                                    <div class="col-lg-9">
                                        <input type="number" name="share_rate_limit_per_day" class="form-control form-control-solid" min="1" step="1" value="{{ old('share_rate_limit_per_day', data_get($shareSettings ?? [], 'share_rate_limit_per_day')) }}" />
                                    </div>
                                </div>

                                <div class="row mb-8">
                                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.share_expires_at') }}</label>
                                    <div class="col-lg-9">
                                        <input type="text" id="kt_projectx_trim_datasheet_share_expires_at" name="share_expires_at" class="form-control form-control-solid" value="{{ old('share_expires_at', data_get($shareSettings ?? [], 'share_expires_at')) }}" placeholder="{{ __('projectx::lang.share_expires_at') }}" />
                                    </div>
                                </div>

                                <div class="row mb-5">
                                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.regenerate_link') }}</label>
                                    <div class="col-lg-9">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" id="trim_regenerate_share_token" name="regenerate_share_token" {{ old('regenerate_share_token') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="trim_regenerate_share_token">{{ __('projectx::lang.regenerate_link_hint') }}</label>
                                        </div>
                                    </div>
                                </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">{{ __('projectx::lang.save_share_settings') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        <div class="col-12">
            @can('projectx.trim.edit')
                @include('projectx::trims._trim_datasheet_edit_form')
            @else
                <div class="card card-flush">
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="fw-bold m-0">{{ __('projectx::lang.trim_datasheet_content') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        @include('projectx::trims._trim_datasheet_content_readonly', ['fds' => $fds ?? []])
                    </div>
                </div>
            @endcan
        </div>
    </div>
@endsection

@section('page_javascript')
    <script>
        (function () {
            const shareExpiresAtInput = document.getElementById('kt_projectx_trim_datasheet_share_expires_at');
            const qcAtInput = document.getElementById('kt_projectx_trim_datasheet_qc_at');

            const initFlatpickr = (element, config) => {
                if (!element) {
                    return;
                }

                if (typeof window.flatpickr === 'function') {
                    window.flatpickr(element, config);
                    return;
                }

                if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.flatpickr === 'function') {
                    window.jQuery(element).flatpickr(config);
                }
            };

            initFlatpickr(shareExpiresAtInput, {
                enableTime: true,
                time_24hr: true,
                altInput: true,
                altFormat: 'd M, Y H:i',
                dateFormat: 'Y-m-d\\TH:i',
                allowInput: false
            });
            initFlatpickr(qcAtInput, {
                enableTime: true,
                time_24hr: true,
                altInput: true,
                altFormat: 'd M, Y H:i',
                dateFormat: 'Y-m-d\\TH:i',
                allowInput: false
            });

            document.querySelectorAll('[data-copy-target]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var input = document.querySelector(button.getAttribute('data-copy-target'));
                    if (!input) {
                        return;
                    }
                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        navigator.clipboard.writeText(input.value || '');
                    } else {
                        input.select();
                        document.execCommand('copy');
                        input.blur();
                    }
                });
            });
        })();
    </script>
@endsection
