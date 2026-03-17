{{-- Form fields matching html/apps/contacts/add-contact.html and edit-contact.html (fv-row mb-7, form-control-solid) --}}
@csrf
@if(isset($contact))
    @method('PUT')
@endif
<input type="hidden" name="contact_type_radio" value="individual" />

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold form-label mt-3"><span class="required">{{ __('contact.contact_type') }}</span></label>
    <select name="type" class="form-select form-select-solid" required id="contact_type">
        <option value="">{{ __('messages.please_select') }}</option>
        @foreach($types ?? [] as $value => $label)
            <option value="{{ $value }}" {{ (old('type', $contact->type ?? $selected_type ?? '') == $value) ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold form-label mt-3">{{ __('lang_v1.contact_id') }}</label>
    <input type="text" class="form-control form-control-solid" name="contact_id" value="{{ old('contact_id', $contact->contact_id ?? '') }}" placeholder="{{ __('lang_v1.leave_empty_to_autogenerate') }}" />
</div>

<div class="fv-row mb-7 customer_group_row">
    <label class="fs-6 fw-semibold form-label mt-3">{{ __('lang_v1.customer_group') }}</label>
    <select name="customer_group_id" class="form-select form-select-solid">
        <option value="">{{ __('lang_v1.none') }}</option>
        @foreach($customer_groups ?? [] as $id => $name)
            @if($id !== '')
                <option value="{{ $id }}" {{ old('customer_group_id', $contact->customer_group_id ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endif
        @endforeach
    </select>
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold form-label mt-3"><span class="required">{{ __('business.name') }}</span></label>
    <input type="text" class="form-control form-control-solid" name="name" value="{{ old('name', isset($contact) ? $contact->name : '') }}" required />
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold form-label mt-3">{{ __('business.business_name') }}</label>
    <input type="text" class="form-control form-control-solid" name="supplier_business_name" value="{{ old('supplier_business_name', $contact->supplier_business_name ?? '') }}" />
</div>

<div class="row row-cols-1 row-cols-sm-2">
    <div class="col">
        <div class="fv-row mb-7">
            <label class="fs-6 fw-semibold form-label mt-3">{{ __('contact.email') }}</label>
            <input type="email" class="form-control form-control-solid" name="email" value="{{ old('email', $contact->email ?? '') }}" />
        </div>
    </div>
    <div class="col">
        <div class="fv-row mb-7">
            <label class="fs-6 fw-semibold form-label mt-3"><span class="required">{{ __('contact.mobile') }}</span></label>
            <input type="text" class="form-control form-control-solid" name="mobile" value="{{ old('mobile', $contact->mobile ?? '') }}" required />
        </div>
    </div>
</div>

<div class="row row-cols-1 row-cols-sm-2">
    <div class="col">
        <div class="fv-row mb-7">
            <label class="fs-6 fw-semibold form-label mt-3">{{ __('business.city') }}</label>
            <input type="text" class="form-control form-control-solid" name="city" value="{{ old('city', $contact->city ?? '') }}" />
        </div>
    </div>
    <div class="col">
        <div class="fv-row mb-7">
            <label class="fs-6 fw-semibold form-label mt-3">{{ __('business.country') }}</label>
            <input type="text" class="form-control form-control-solid" name="country" value="{{ old('country', $contact->country ?? '') }}" />
        </div>
    </div>
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold form-label mt-3">{{ __('lang_v1.address') }}</label>
    <input type="text" class="form-control form-control-solid" name="address_line_1" value="{{ old('address_line_1', $contact->address_line_1 ?? '') }}" />
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold form-label mt-3">{{ __('lang_v1.pay_term') }}</label>
    <div class="d-flex gap-2">
        <input type="number" class="form-control form-control-solid" name="pay_term_number" value="{{ old('pay_term_number', $contact->pay_term_number ?? '') }}" placeholder="0" min="0" />
        <select name="pay_term_type" class="form-select form-select-solid w-120px">
            <option value="">{{ __('messages.please_select') }}</option>
            <option value="days" {{ old('pay_term_type', $contact->pay_term_type ?? '') == 'days' ? 'selected' : '' }}>{{ __('lang_v1.days') }}</option>
            <option value="months" {{ old('pay_term_type', $contact->pay_term_type ?? '') == 'months' ? 'selected' : '' }}>{{ __('lang_v1.months') }}</option>
        </select>
    </div>
</div>

<div class="row row-cols-1 row-cols-sm-2">
    <div class="col">
        <div class="fv-row mb-7">
            <label class="fs-6 fw-semibold form-label mt-3">{{ __('lang_v1.opening_balance') }}</label>
            <input type="text" class="form-control form-control-solid" name="opening_balance" value="{{ old('opening_balance', $opening_balance ?? '0') }}" />
        </div>
    </div>
    <div class="col">
        <div class="fv-row mb-7">
            <label class="fs-6 fw-semibold form-label mt-3">{{ __('lang_v1.credit_limit') }}</label>
            <input type="text" class="form-control form-control-solid" name="credit_limit" value="{{ old('credit_limit', isset($contact) && $contact->credit_limit !== null ? $contact->credit_limit : '') }}" placeholder="{{ __('lang_v1.no_limit') }}" />
        </div>
    </div>
</div>

<div class="fv-row mb-7">
    <label class="fs-6 fw-semibold form-label mt-3">Notes</label>
    <textarea class="form-control form-control-solid" name="custom_field1" rows="3" placeholder="Notes">{{ old('custom_field1', $contact->custom_field1 ?? '') }}</textarea>
</div>

<div class="separator mb-6"></div>
<div class="d-flex justify-content-end">
    <a href="{{ route('projectx.contacts.index') }}" class="btn btn-light me-3">{{ __('messages.cancel') }}</a>
    <button type="submit" class="btn btn-primary">
        <span class="indicator-label">{{ __('messages.save') }}</span>
        <span class="indicator-progress">{{ __('messages.please_wait') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
    </button>
</div>
