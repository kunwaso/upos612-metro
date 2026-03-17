@if ($errors->any())
    <div class="alert alert-danger mb-8">
        <ul class="mb-0 ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="stepper d-flex flex-column flex-row-fluid" id="kt_core_register_stepper">
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
                    <input type="text" id="kt_core_register_start_date" name="start_date" value="{{ old('start_date') }}"
                        placeholder="{{ config('constants.default_date_format') }}"
                        class="form-control form-control-solid {{ $errors->has('start_date') ? 'is-invalid' : '' }}"
                        data-kt-date-format="{{ config('constants.default_date_format') }}" readonly>
                    @error('start_date')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.currency') }}</label>
                    <select name="currency_id" required
                        class="form-select form-select-solid {{ $errors->has('currency_id') ? 'is-invalid' : '' }}">
                        <option value="">{{ __('business.currency_placeholder') }}</option>
                        @foreach ($currencies as $currencyId => $currencyName)
                            <option value="{{ $currencyId }}" {{ (string) old('currency_id') === (string) $currencyId ? 'selected' : '' }}>
                                {{ $currencyName }}
                            </option>
                        @endforeach
                    </select>
                    @error('currency_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label">{{ __('business.upload_logo') }}</label>
                    <input type="file" name="business_logo" accept="image/*"
                        class="form-control form-control-solid {{ $errors->has('business_logo') ? 'is-invalid' : '' }}">
                    @error('business_logo')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label">{{ __('lang_v1.website') }}</label>
                    <input type="url" name="website" value="{{ old('website') }}"
                        class="form-control form-control-solid {{ $errors->has('website') ? 'is-invalid' : '' }}">
                    @error('website')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label">{{ __('lang_v1.business_telephone') }}</label>
                    <input type="text" name="mobile" value="{{ old('mobile') }}"
                        class="form-control form-control-solid {{ $errors->has('mobile') ? 'is-invalid' : '' }}">
                    @error('mobile')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label">{{ __('business.alternate_number') }}</label>
                    <input type="text" name="alternate_number" value="{{ old('alternate_number') }}"
                        class="form-control form-control-solid {{ $errors->has('alternate_number') ? 'is-invalid' : '' }}">
                    @error('alternate_number')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.country') }}</label>
                    <input type="text" name="country" value="{{ old('country') }}" required
                        class="form-control form-control-solid {{ $errors->has('country') ? 'is-invalid' : '' }}">
                    @error('country')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.state') }}</label>
                    <input type="text" name="state" value="{{ old('state') }}" required
                        class="form-control form-control-solid {{ $errors->has('state') ? 'is-invalid' : '' }}">
                    @error('state')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.city') }}</label>
                    <input type="text" name="city" value="{{ old('city') }}" required
                        class="form-control form-control-solid {{ $errors->has('city') ? 'is-invalid' : '' }}">
                    @error('city')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.zip_code') }}</label>
                    <input type="text" name="zip_code" value="{{ old('zip_code') }}" required
                        class="form-control form-control-solid {{ $errors->has('zip_code') ? 'is-invalid' : '' }}">
                    @error('zip_code')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.landmark') }}</label>
                    <input type="text" name="landmark" value="{{ old('landmark') }}" required
                        class="form-control form-control-solid {{ $errors->has('landmark') ? 'is-invalid' : '' }}">
                    @error('landmark')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.time_zone') }}</label>
                    <select name="time_zone" required
                        class="form-select form-select-solid {{ $errors->has('time_zone') ? 'is-invalid' : '' }}">
                        <option value="">{{ __('business.time_zone') }}</option>
                        @foreach ($timezone_list as $timezoneKey => $timezoneLabel)
                            <option value="{{ $timezoneKey }}" {{ (string) old('time_zone', config('app.timezone')) === (string) $timezoneKey ? 'selected' : '' }}>
                                {{ $timezoneLabel }}
                            </option>
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
                    <input type="text" name="tax_label_1" value="{{ old('tax_label_1') }}"
                        class="form-control form-control-solid {{ $errors->has('tax_label_1') ? 'is-invalid' : '' }}">
                    @error('tax_label_1')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label">{{ __('business.tax_1_no') }}</label>
                    <input type="text" name="tax_number_1" value="{{ old('tax_number_1') }}"
                        class="form-control form-control-solid {{ $errors->has('tax_number_1') ? 'is-invalid' : '' }}">
                    @error('tax_number_1')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label">{{ __('business.tax_2_name') }}</label>
                    <input type="text" name="tax_label_2" value="{{ old('tax_label_2') }}"
                        class="form-control form-control-solid {{ $errors->has('tax_label_2') ? 'is-invalid' : '' }}">
                    @error('tax_label_2')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label">{{ __('business.tax_2_no') }}</label>
                    <input type="text" name="tax_number_2" value="{{ old('tax_number_2') }}"
                        class="form-control form-control-solid {{ $errors->has('tax_number_2') ? 'is-invalid' : '' }}">
                    @error('tax_number_2')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.fy_start_month') }}</label>
                    <select name="fy_start_month" required
                        class="form-select form-select-solid {{ $errors->has('fy_start_month') ? 'is-invalid' : '' }}">
                        <option value="">{{ __('business.fy_start_month') }}</option>
                        @foreach ($months as $monthKey => $monthLabel)
                            <option value="{{ $monthKey }}" {{ (string) old('fy_start_month', $default_fy_start_month) === (string) $monthKey ? 'selected' : '' }}>
                                {{ $monthLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('fy_start_month')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.accounting_method') }}</label>
                    <select name="accounting_method" required
                        class="form-select form-select-solid {{ $errors->has('accounting_method') ? 'is-invalid' : '' }}">
                        <option value="">{{ __('business.accounting_method') }}</option>
                        @foreach ($accounting_methods as $methodKey => $methodLabel)
                            <option value="{{ $methodKey }}" {{ (string) old('accounting_method', $default_accounting_method) === (string) $methodKey ? 'selected' : '' }}>
                                {{ $methodLabel }}
                            </option>
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
                    <input type="text" name="surname" value="{{ old('surname') }}"
                        class="form-control form-control-solid {{ $errors->has('surname') ? 'is-invalid' : '' }}">
                    @error('surname')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 fv-row">
                    <label class="form-label required">{{ __('business.first_name') }}</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                        class="form-control form-control-solid {{ $errors->has('first_name') ? 'is-invalid' : '' }}">
                    @error('first_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 fv-row">
                    <label class="form-label">{{ __('business.last_name') }}</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}"
                        class="form-control form-control-solid {{ $errors->has('last_name') ? 'is-invalid' : '' }}">
                    @error('last_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.username') }}</label>
                    <input type="text" name="username" value="{{ old('username') }}" required
                        class="form-control form-control-solid {{ $errors->has('username') ? 'is-invalid' : '' }}">
                    @error('username')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="form-control form-control-solid {{ $errors->has('email') ? 'is-invalid' : '' }}">
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.password') }}</label>
                    <input type="password" name="password" required
                        class="form-control form-control-solid {{ $errors->has('password') ? 'is-invalid' : '' }}">
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 fv-row">
                    <label class="form-label required">{{ __('business.confirm_password') }}</label>
                    <input type="password" name="confirm_password" required
                        class="form-control form-control-solid {{ $errors->has('confirm_password') ? 'is-invalid' : '' }}">
                    @error('confirm_password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div data-kt-stepper-element="content">
            @if (!empty($system_settings['superadmin_enable_register_tc']) && !empty($is_register))
                <div class="fv-row mb-8">
                    <label class="form-check form-check-custom form-check-solid align-items-start">
                        <input type="checkbox" name="accept_tc" value="1" required
                            class="form-check-input mt-1 {{ $errors->has('accept_tc') ? 'is-invalid' : '' }}"
                            {{ old('accept_tc') ? 'checked' : '' }}>
                        <span class="form-check-label fw-semibold text-gray-700 fs-6 ms-3">
                            {{ __('lang_v1.accept_terms_and_conditions') }}
                            <a href="#" class="link-primary fw-bold" data-bs-toggle="modal" data-bs-target="#tc_modal">
                                {{ __('lang_v1.terms_conditions') }}
                            </a>
                        </span>
                    </label>
                    @error('accept_tc')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                @include('business.partials.terms_conditions')
            @endif

            @if (config('constants.enable_recaptcha') && !empty($is_register))
                <div class="fv-row mb-8">
                    <div class="g-recaptcha" data-sitekey="{{ config('constants.google_recaptcha_key') }}"></div>
                    @if ($errors->has('g-recaptcha-response'))
                        <div id="core-register-recaptcha-error" class="text-danger fs-7 mt-2">
                            {{ $errors->first('g-recaptcha-response') }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex flex-stack pt-10">
            <button type="button" class="btn btn-light-primary d-none" data-kt-stepper-action="previous">
                {{ __('pagination.previous') }}
            </button>
            <div class="ms-auto">
                <button type="button" class="btn btn-primary" data-kt-stepper-action="next">
                    {{ __('pagination.next') }}
                </button>
                <button type="button" class="btn btn-primary d-none" data-kt-stepper-action="submit">
                    <span class="indicator-label">{{ __('business.register_now') }}</span>
                    <span class="indicator-progress">Please wait...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
