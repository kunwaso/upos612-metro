@extends('projectx::layouts.main')

@section('title', $contact->name ?? __('contact.contact'))

@section('content')
@include('projectx::contacts.partials._toolbar', [
    'title' => $contact->name ?? __('contact.contact'),
    'breadcrumb' => __('messages.view'),
    'filterDropdown' => false,
    'primaryAction' => (auth()->user()->can('supplier.update') || auth()->user()->can('customer.update'))
        ? '<a href="' . route('projectx.contacts.edit', $contact->id) . '" class="btn btn-sm btn-primary">' . __('messages.edit') . '</a>'
        : null,
])

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="row g-7">
            @include('projectx::contacts.partials._groups_card', [
                'type' => $showType ?? 'customer',
                'contacts' => $showContacts ?? [],
                'contactsCount' => $contactsCount ?? 0,
                'customerCount' => $customerCount ?? 0,
                'supplierCount' => $supplierCount ?? 0,
                'customer_groups' => $customer_groups ?? [],
            ])
            @include('projectx::contacts.partials._list_card', ['contacts' => $showContacts ?? [], 'contact' => $contact])

            <div class="col-xl-6">
                <div class="card card-flush h-lg-100" id="kt_contacts_main">
                    <div class="card-header pt-7" id="kt_chat_contacts_header">
                        <div class="card-title">
                            <i class="ki-duotone ki-badge fs-1 me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                            <h2>Contact Details</h2>
                        </div>
                        <div class="card-toolbar gap-3">
                            @if(auth()->user()->can('supplier.update') || auth()->user()->can('customer.update'))
                                <a href="{{ route('projectx.contacts.edit', $contact->id) }}" class="btn btn-sm btn-light btn-active-light-primary">{{ __('messages.edit') }}</a>
                            @endif
                            <div class="dropdown">
                                <a href="#" class="btn btn-sm btn-icon btn-light btn-active-light-primary" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-dots-square fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('projectx.contacts.edit', $contact->id) }}">{{ __('messages.edit') }}</a></li>
                                    @if((auth()->user()->can('supplier.delete') || auth()->user()->can('customer.delete')) && !$contact->is_default)
                                        <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#delete_contact_modal">{{ __('messages.delete') }}</a></li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-5">
                        <div class="d-flex gap-7 align-items-center">
                            <div class="symbol symbol-circle symbol-100px">
                                <span class="symbol-label bg-light-primary text-primary fs-2x fw-bolder">{{ strtoupper(substr($contact->name ?? '?', 0, 1)) }}</span>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <h3 class="mb-0">{{ $contact->name }}</h3>
                                @if($contact->email)
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ki-duotone ki-sms fs-2"><span class="path1"></span><span class="path2"></span></i>
                                        <a href="mailto:{{ $contact->email }}" class="text-muted text-hover-primary">{{ $contact->email }}</a>
                                    </div>
                                @endif
                                @if($contact->mobile)
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ki-duotone ki-phone fs-2"><span class="path1"></span><span class="path2"></span></i>
                                        <span class="text-muted">{{ $contact->mobile }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x fs-6 fw-semibold mt-6 mb-8 gap-2">
                            <li class="nav-item">
                                <a class="nav-link text-active-primary d-flex align-items-center pb-4 active" data-bs-toggle="tab" href="#kt_contact_view_general">General</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="kt_contact_view_general">
                                <div class="d-flex flex-column gap-5 mt-7">
                                    @if($contact->supplier_business_name)
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fw-bold text-muted">{{ __('business.business_name') }}</div>
                                            <div class="fw-bold fs-5">{{ $contact->supplier_business_name }}</div>
                                        </div>
                                    @endif
                                    @if($contact->contact_id)
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fw-bold text-muted">{{ __('lang_v1.contact_id') }}</div>
                                            <div class="fw-bold fs-5">{{ $contact->contact_id }}</div>
                                        </div>
                                    @endif
                                    @if($contact->type)
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fw-bold text-muted">{{ __('contact.contact_type') }}</div>
                                            <div class="fw-bold fs-5">{{ ucfirst($contact->type) }}</div>
                                        </div>
                                    @endif
                                    @if($contact->city)
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fw-bold text-muted">{{ __('business.city') }}</div>
                                            <div class="fw-bold fs-5">{{ $contact->city }}</div>
                                        </div>
                                    @endif
                                    @if($contact->country)
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fw-bold text-muted">{{ __('business.country') }}</div>
                                            <div class="fw-bold fs-5">{{ $contact->country }}</div>
                                        </div>
                                    @endif
                                    @if($contact->address_line_1)
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fw-bold text-muted">{{ __('lang_v1.address') }}</div>
                                            <div class="fw-bold fs-5">{{ $contact->address_line_1 }}{{ $contact->address_line_2 ? ', ' . $contact->address_line_2 : '' }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if((auth()->user()->can('supplier.delete') || auth()->user()->can('customer.delete')) && !$contact->is_default)
<div class="modal fade" id="delete_contact_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.delete') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">{{ __('messages.confirm_delete') }}</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <form action="{{ route('projectx.contacts.destroy', $contact->id) }}" method="post" class="d-inline" id="delete_contact_form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ __('messages.delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('page_javascript')
<script>
document.getElementById('delete_contact_form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var fd = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success && data.redirect) window.location.href = data.redirect;
        else if (data.msg) alert(data.msg);
    }).catch(function() { form.submit(); });
});
</script>
@endsection
