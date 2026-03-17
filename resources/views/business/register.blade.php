@extends('layouts.auth2')

@section('title', __('business.register_now'))
@section('aside_title', __('business.register_now'))
@section('aside_subtitle', __('business.register_and_get_started_in_minutes'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('auth_content')
    <div class="w-100" data-auth-view="business-register">
        <div class="text-center mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">{{ __('business.register_now') }}</h1>
            <div class="text-gray-500 fw-semibold fs-6">{{ __('business.register_and_get_started_in_minutes') }}</div>
        </div>

        <form id="business_register_form" class="form w-100" method="POST" action="{{ route('business.postRegister') }}"
            enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="language" value="{{ $registration_language }}">
            <input type="hidden" name="package_id" value="{{ $package_id }}">

            @include('business.partials.register_form', ['is_register' => true])
        </form>

        <div class="text-gray-500 text-center fw-semibold fs-6 mt-10">
            {{ __('business.already_registered') }}
            <a href="{{ route('login', ['lang' => request()->query('lang')]) }}" class="link-primary fw-semibold">
                {{ __('business.sign_in') }}
            </a>
        </div>
    </div>
@endsection

@section('javascript')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @if (config('constants.enable_recaptcha'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
    <script>
        (function () {
            var stepperElement = document.getElementById('kt_core_register_stepper');
            var form = document.getElementById('business_register_form');

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
                navItems.forEach(function (item, index) {
                    var itemStep = index + 1;
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

                contents.forEach(function (item, index) {
                    var itemStep = index + 1;
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
                if (previousButton) {
                    previousButton.classList.toggle('d-none', current === 1);
                }
                if (nextButton) {
                    nextButton.classList.toggle('d-none', isLast);
                }
                if (submitButton) {
                    submitButton.classList.toggle('d-none', !isLast);
                }
            };

            var currentContent = function () {
                return stepperElement.querySelector('[data-kt-stepper-element="content"].current') ||
                    contents[stepper.getCurrentStepIndex() - 1] || null;
            };

            var validateField = function (field) {
                field.classList.remove('is-invalid');

                if (!field.checkValidity()) {
                    field.classList.add('is-invalid');
                    return false;
                }

                return true;
            };

            var validateStep = function () {
                var content = currentContent();
                if (!content) {
                    return true;
                }

                var valid = true;
                content.querySelectorAll('input,select,textarea').forEach(function (field) {
                    if (field.type === 'hidden' || field.disabled) {
                        return;
                    }

                    if (!validateField(field)) {
                        valid = false;
                    }
                });

                var confirmPassword = form.querySelector('input[name="confirm_password"]');
                var password = form.querySelector('input[name="password"]');
                if (confirmPassword && password && content.contains(confirmPassword) && confirmPassword.value !== password.value) {
                    confirmPassword.classList.add('is-invalid');
                    valid = false;
                }

                if (!valid) {
                    var firstInvalidField = content.querySelector('.is-invalid');
                    if (firstInvalidField) {
                        if (typeof firstInvalidField.focus === 'function') {
                            firstInvalidField.focus({ preventScroll: true });
                        }
                        if (typeof firstInvalidField.scrollIntoView === 'function') {
                            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        if (typeof firstInvalidField.reportValidity === 'function') {
                            firstInvalidField.reportValidity();
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

            form.querySelectorAll('input,select,textarea').forEach(function (field) {
                field.addEventListener('input', function () {
                    if (field.classList.contains('is-invalid')) {
                        validateField(field);
                    }
                });
                field.addEventListener('change', function () {
                    if (field.classList.contains('is-invalid')) {
                        validateField(field);
                    }
                });
            });

            var serverInvalid = form.querySelector('.is-invalid');
            if (serverInvalid) {
                var invalidContent = serverInvalid.closest('[data-kt-stepper-element="content"]');
                if (invalidContent) {
                    var stepIndex = contents.indexOf(invalidContent);
                    if (stepIndex >= 0) {
                        stepper.goTo(stepIndex + 1);
                    }
                }
            } else if (document.getElementById('core-register-recaptcha-error')) {
                stepper.goTo(contents.length);
            }

            syncStepperUi(stepper.getCurrentStepIndex());
            updateButtons();

            var startDateInput = document.getElementById('kt_core_register_start_date');
            if (startDateInput && typeof window.flatpickr !== 'undefined') {
                window.flatpickr(startDateInput, {
                    dateFormat: startDateInput.getAttribute('data-kt-date-format') || 'm/d/Y',
                    allowInput: false,
                    maxDate: 'today'
                });
            }
        })();
    </script>
@endsection
