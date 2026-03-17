@extends('projectx::site_manager.auth.layout')

@section('title', __('business.register_now'))
@section('aside_title', __('business.register_now'))
@section('aside_subtitle', config('app.name', 'ultimatePOS'))

@section('auth_content')
    <div class="w-100" data-auth-view="projectx-register">
        <div class="text-center mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">{{ __('business.register_now') }}</h1>
            <div class="text-gray-500 fw-semibold fs-6">{{ __('business.register_and_get_started_in_minutes') }}</div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-8">
                <ul class="mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="kt_projectx_register_form" class="form w-100" method="POST" action="{{ route('business.postRegister') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="language" value="{{ request()->lang }}">
            <input type="hidden" name="package_id" value="{{ $package_id ?? '' }}">
            @php
                $defaultFyStartMonth = (string) old('fy_start_month', date('n'));
                $defaultAccountingMethod = (string) old('accounting_method', 'fifo');
                if (! array_key_exists($defaultAccountingMethod, $accounting_methods)) {
                    $defaultAccountingMethod = (string) (array_key_first($accounting_methods) ?? '');
                }
            @endphp

            <div class="stepper d-flex flex-column flex-row-fluid" id="kt_projectx_register_stepper">
                <div class="stepper-nav nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-6 fw-bold mb-8">
                    <div class="stepper-item nav-item current" data-kt-stepper-element="nav">
                        <h3 class="stepper-title nav-link mb-0">{{ __('business.business_details') }}</h3>
                    </div>
                    <div class="stepper-item nav-item" data-kt-stepper-element="nav">
                        <h3 class="stepper-title nav-link mb-0">{{ __('business.business_settings') }}</h3>
                    </div>
                    <div class="stepper-item nav-item" data-kt-stepper-element="nav">
                        <h3 class="stepper-title nav-link mb-0">{{ __('business.owner_info') }}</h3>
                    </div>
                    <div class="stepper-item nav-item" data-kt-stepper-element="nav">
                        <h3 class="stepper-title nav-link mb-0 text-white">{{ __('business.register_now') }}</h3>
                    </div>
                </div>
                <div class="w-100">
                <div class="current" data-kt-stepper-element="content">
                    <div class="row g-5">
                        <div class="col-md-12 fv-row">
                            <label class="form-label required">{{ __('business.business_name') }}</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="form-control form-control-solid {{ $errors->has('name') ? 'is-invalid' : '' }}">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label">{{ __('business.start_date') }}</label>
                            <input type="text" id="kt_projectx_register_start_date" name="start_date" value="{{ old('start_date') }}" placeholder="{{ config('constants.default_date_format') }}"
                                class="form-control form-control-solid {{ $errors->has('start_date') ? 'is-invalid' : '' }}" data-kt-projectx-date-format="{{ config('constants.default_date_format') }}" readonly>
                            @error('start_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.currency') }}</label>
                            <select name="currency_id" required class="form-select form-select-solid {{ $errors->has('currency_id') ? 'is-invalid' : '' }}">
                                <option value="">{{ __('business.currency_placeholder') }}</option>
                                @foreach ($currencies as $currencyId => $currencyName)
                                    <option value="{{ $currencyId }}" {{ (string) old('currency_id') === (string) $currencyId ? 'selected' : '' }}>{{ $currencyName }}</option>
                                @endforeach
                            </select>
                            @error('currency_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label">{{ __('business.upload_logo') }}</label>
                            <input type="file" name="business_logo" accept="image/*" class="form-control form-control-solid {{ $errors->has('business_logo') ? 'is-invalid' : '' }}">
                            @error('business_logo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label">{{ __('lang_v1.website') }}</label>
                            <input type="text" name="website" value="{{ old('website') }}" class="form-control form-control-solid {{ $errors->has('website') ? 'is-invalid' : '' }}">
                            @error('website')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label">{{ __('lang_v1.business_telephone') }}</label>
                            <input type="text" name="mobile" value="{{ old('mobile') }}" class="form-control form-control-solid {{ $errors->has('mobile') ? 'is-invalid' : '' }}">
                            @error('mobile')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label">{{ __('business.alternate_number') }}</label>
                            <input type="text" name="alternate_number" value="{{ old('alternate_number') }}" class="form-control form-control-solid {{ $errors->has('alternate_number') ? 'is-invalid' : '' }}">
                            @error('alternate_number')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.country') }}</label>
                            <input type="text" name="country" value="{{ old('country') }}" required class="form-control form-control-solid {{ $errors->has('country') ? 'is-invalid' : '' }}">
                            @error('country')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.state') }}</label>
                            <input type="text" name="state" value="{{ old('state') }}" required class="form-control form-control-solid {{ $errors->has('state') ? 'is-invalid' : '' }}">
                            @error('state')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.city') }}</label>
                            <input type="text" name="city" value="{{ old('city') }}" required class="form-control form-control-solid {{ $errors->has('city') ? 'is-invalid' : '' }}">
                            @error('city')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.zip_code') }}</label>
                            <input type="text" name="zip_code" value="{{ old('zip_code') }}" required class="form-control form-control-solid {{ $errors->has('zip_code') ? 'is-invalid' : '' }}">
                            @error('zip_code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.landmark') }}</label>
                            <input type="text" name="landmark" value="{{ old('landmark') }}" required class="form-control form-control-solid {{ $errors->has('landmark') ? 'is-invalid' : '' }}">
                            @error('landmark')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.time_zone') }}</label>
                            <select name="time_zone" required class="form-select form-select-solid {{ $errors->has('time_zone') ? 'is-invalid' : '' }}">
                                <option value="">{{ __('business.time_zone') }}</option>
                                @foreach ($timezone_list as $timezoneKey => $timezoneLabel)
                                    <option value="{{ $timezoneKey }}" {{ (string) old('time_zone', config('app.timezone')) === (string) $timezoneKey ? 'selected' : '' }}>{{ $timezoneLabel }}</option>
                                @endforeach
                            </select>
                            @error('time_zone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div data-kt-stepper-element="content">
                    <div class="row g-5">
                        <div class="col-md-6 fv-row">
                            <label class="form-label">{{ __('business.tax_1_name') }}</label>
                            <input type="text" name="tax_label_1" value="{{ old('tax_label_1') }}" class="form-control form-control-solid {{ $errors->has('tax_label_1') ? 'is-invalid' : '' }}">
                            @error('tax_label_1')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label">{{ __('business.tax_1_no') }}</label>
                            <input type="text" name="tax_number_1" value="{{ old('tax_number_1') }}" class="form-control form-control-solid {{ $errors->has('tax_number_1') ? 'is-invalid' : '' }}">
                            @error('tax_number_1')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label">{{ __('business.tax_2_name') }}</label>
                            <input type="text" name="tax_label_2" value="{{ old('tax_label_2') }}" class="form-control form-control-solid {{ $errors->has('tax_label_2') ? 'is-invalid' : '' }}">
                            @error('tax_label_2')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label">{{ __('business.tax_2_no') }}</label>
                            <input type="text" name="tax_number_2" value="{{ old('tax_number_2') }}" class="form-control form-control-solid {{ $errors->has('tax_number_2') ? 'is-invalid' : '' }}">
                            @error('tax_number_2')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.fy_start_month') }}</label>
                            <select name="fy_start_month" required class="form-select form-select-solid {{ $errors->has('fy_start_month') ? 'is-invalid' : '' }}">
                                <option value="">{{ __('business.fy_start_month') }}</option>
                                @foreach ($months as $monthKey => $monthLabel)
                                    <option value="{{ $monthKey }}" {{ $defaultFyStartMonth === (string) $monthKey ? 'selected' : '' }}>{{ $monthLabel }}</option>
                                @endforeach
                            </select>
                            @error('fy_start_month')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.accounting_method') }}</label>
                            <select name="accounting_method" required class="form-select form-select-solid {{ $errors->has('accounting_method') ? 'is-invalid' : '' }}">
                                <option value="">{{ __('business.accounting_method') }}</option>
                                @foreach ($accounting_methods as $methodKey => $methodLabel)
                                    <option value="{{ $methodKey }}" {{ $defaultAccountingMethod === (string) $methodKey ? 'selected' : '' }}>{{ $methodLabel }}</option>
                                @endforeach
                            </select>
                            @error('accounting_method')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div data-kt-stepper-element="content">
                    <div class="row g-5">
                        <div class="col-md-4 fv-row">
                            <label class="form-label">{{ __('business.prefix') }}</label>
                            <input type="text" name="surname" value="{{ old('surname') }}" class="form-control form-control-solid {{ $errors->has('surname') ? 'is-invalid' : '' }}">
                            @error('surname')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 fv-row">
                            <label class="form-label required">{{ __('business.first_name') }}</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required class="form-control form-control-solid {{ $errors->has('first_name') ? 'is-invalid' : '' }}">
                            @error('first_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 fv-row">
                            <label class="form-label">{{ __('business.last_name') }}</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control form-control-solid {{ $errors->has('last_name') ? 'is-invalid' : '' }}">
                            @error('last_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.username') }}</label>
                            <input type="text" name="username" value="{{ old('username') }}" required class="form-control form-control-solid {{ $errors->has('username') ? 'is-invalid' : '' }}">
                            @error('username')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.email') }}</label>
                            <input type="email" name="email" value="{{ old('email') }}" required class="form-control form-control-solid {{ $errors->has('email') ? 'is-invalid' : '' }}">
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row" data-kt-password-meter="true">
                            <label class="form-label required">{{ __('business.password') }}</label>
                            <div class="position-relative">
                                <input type="password" name="password" required class="form-control form-control-solid {{ $errors->has('password') ? 'is-invalid' : '' }}">
                                <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
                                    <i class="ki-duotone ki-eye-slash fs-2"></i>
                                    <i class="ki-duotone ki-eye fs-2 d-none"></i>
                                </span>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="form-label required">{{ __('business.confirm_password') }}</label>
                            <input type="password" name="confirm_password" required class="form-control form-control-solid {{ $errors->has('confirm_password') ? 'is-invalid' : '' }}">
                            @error('confirm_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div data-kt-stepper-element="content">
                    @if (!empty($system_settings['superadmin_enable_register_tc']) && !empty($is_register))
                        <div class="fv-row mb-8">
                            <label class="form-check form-check-custom form-check-solid">
                                <input type="checkbox" name="accept_tc" value="1" required class="form-check-input {{ $errors->has('accept_tc') ? 'is-invalid' : '' }}" {{ old('accept_tc') ? 'checked' : '' }}>
                                <span class="form-check-label fw-semibold text-gray-700 fs-6 ms-2">
                                    {{ __('lang_v1.accept_terms_and_conditions') }}
                                    <a href="#" class="link-primary fw-bold" data-bs-toggle="modal" data-bs-target="#tc_modal">{{ __('lang_v1.terms_conditions') }}</a>
                                </span>
                            </label>
                            @error('accept_tc')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        @include('projectx::site_manager.auth.partials.terms_conditions')
                    @endif

                    @if (config('constants.enable_recaptcha') && !empty($is_register))
                        <div class="fv-row mb-8">
                            <div class="g-recaptcha" data-sitekey="{{ config('constants.google_recaptcha_key') }}"></div>
                            @if ($errors->has('g-recaptcha-response'))
                                <div id="projectx-recaptcha-error" class="text-danger fs-7 mt-2">{{ $errors->first('g-recaptcha-response') }}</div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-stack pt-10">
                    <button type="button" class="btn btn-light-primary d-none" data-kt-stepper-action="previous">{{ __('projectx::lang.previous') }}</button>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-primary" data-kt-stepper-action="next">{{ __('projectx::lang.next') }}</button>
                        <button type="button" class="btn btn-primary d-none" data-kt-stepper-action="submit">
                            <span class="indicator-label">{{ __('business.register_now') }}</span>
                            <span class="indicator-progress">{{ __('projectx::lang.please_wait') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
                </div>
            </div>
        </form>

        <div class="text-gray-500 text-center fw-semibold fs-6 mt-10">
            {{ __('business.not_yet_registered') }}
            <a href="{{ route('login') }}" class="link-primary fw-semibold">{{ __('business.sign_in') }}</a>
        </div>
    </div>
@endsection

@section('javascript')
    {{-- Single date picker: load flatpickr if not already present (auth layout may not include it) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @if (config('constants.enable_recaptcha') && !empty($is_register))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
    <script>
        (function () {
            var stepperElement = document.getElementById('kt_projectx_register_stepper');
            var form = document.getElementById('kt_projectx_register_form');
            if (!stepperElement || !form || typeof KTStepper === 'undefined') {
                return;
            }

            var stepper = new KTStepper(stepperElement);
            var previousButton = stepperElement.querySelector('[data-kt-stepper-action="previous"]');
            var nextButton = stepperElement.querySelector('[data-kt-stepper-action="next"]');
            var submitButton = stepperElement.querySelector('[data-kt-stepper-action="submit"]');
            var navItems = Array.prototype.slice.call(stepperElement.querySelectorAll('[data-kt-stepper-element="nav"]'));
            var contents = Array.prototype.slice.call(stepperElement.querySelectorAll('[data-kt-stepper-element="content"]'));

            var syncStepperUi = function (stepIndex) {
                navItems.forEach(function (item, idx) {
                    var itemStep = idx + 1;
                    item.classList.remove('current', 'completed', 'pending');
                    if (itemStep < stepIndex) {
                        item.classList.add('completed');
                    } else if (itemStep === stepIndex) {
                        item.classList.add('current');
                    } else {
                        item.classList.add('pending');
                    }

                    var navLink = item.querySelector('.stepper-title.nav-link');
                    if (navLink) {
                        navLink.classList.toggle('active', itemStep === stepIndex);
                    }
                });

                contents.forEach(function (item, idx) {
                    var itemStep = idx + 1;
                    item.classList.remove('current', 'completed', 'pending');
                    if (itemStep < stepIndex) {
                        item.classList.add('completed');
                    } else if (itemStep === stepIndex) {
                        item.classList.add('current');
                    } else {
                        item.classList.add('pending');
                    }
                });
            };

            var updateButtons = function () {
                var current = stepper.getCurrentStepIndex();
                if (current < 1) {
                    current = 1;
                }
                if (current > contents.length) {
                    current = contents.length;
                }
                var isLast = current === contents.length;
                if (previousButton) previousButton.classList.toggle('d-none', current === 1);
                if (nextButton) nextButton.classList.toggle('d-none', isLast);
                if (submitButton) submitButton.classList.toggle('d-none', !isLast);
            };

            var currentContent = function () {
                return stepperElement.querySelector('[data-kt-stepper-element="content"].current') ||
                    contents[stepper.getCurrentStepIndex() - 1] || null;
            };

            var validateStep = function () {
                var content = currentContent();
                if (!content) {
                    return true;
                }

                var valid = true;
                content.querySelectorAll('input[required],select[required],textarea[required]').forEach(function (field) {
                    field.classList.remove('is-invalid');
                    if (!field.checkValidity()) {
                        field.classList.add('is-invalid');
                        valid = false;
                    }
                });

                if (!valid) {
                    var first = content.querySelector('.is-invalid');
                    if (first) {
                        if (typeof first.focus === 'function') {
                            first.focus({ preventScroll: true });
                        }
                        if (typeof first.scrollIntoView === 'function') {
                            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        if (typeof first.reportValidity === 'function') {
                            first.reportValidity();
                        }
                    }
                }

                return valid;
            };

            stepper.on('kt.stepper.next', function (stepperObj) {
                if (validateStep()) {
                    stepperObj.goNext();
                }
            });

            stepper.on('kt.stepper.previous', function (stepperObj) {
                stepperObj.goPrevious();
            });

            stepper.on('kt.stepper.changed', function () {
                syncStepperUi(stepper.getCurrentStepIndex());
                updateButtons();
            });

            if (submitButton) {
                submitButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (!validateStep()) {
                        return;
                    }
                    submitButton.disabled = true;
                    submitButton.setAttribute('data-kt-indicator', 'on');
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            }

            var serverInvalid = form.querySelector('.is-invalid');
            if (serverInvalid) {
                var invalidContent = serverInvalid.closest('[data-kt-stepper-element="content"]');
                if (invalidContent) {
                    var stepIndex = contents.indexOf(invalidContent);
                    if (stepIndex >= 0) {
                        stepper.goTo(stepIndex + 1);
                    }
                }
            } else if (document.getElementById('projectx-recaptcha-error')) {
                stepper.goTo(contents.length);
            }

            syncStepperUi(stepper.getCurrentStepIndex());
            updateButtons();

            // Single date picker for start_date (Metronic uses flatpickr via plugins.bundle or jQuery)
            var startDateEl = document.getElementById('kt_projectx_register_start_date');
            if (startDateEl) {
                var dateFormat = startDateEl.getAttribute('data-kt-projectx-date-format') || 'm/d/Y';
                if (typeof window.flatpickr !== 'undefined') {
                    window.flatpickr(startDateEl, {
                        dateFormat: dateFormat,
                        allowInput: false
                    });
                } else if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.flatpickr) {
                    window.jQuery(startDateEl).flatpickr({
                        dateFormat: dateFormat,
                        allowInput: false
                    });
                }
            }
        })();
    </script>
@endsection
