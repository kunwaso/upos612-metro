@extends('projectx::layouts.main')

@section('title', __('messages.edit') . ' - ' . ($contact->name ?? __('contact.contact')))

@section('content')
@include('projectx::contacts.partials._toolbar', [
    'title' => __('messages.edit') . ' ' . ($contact->name ?? ''),
    'breadcrumb' => __('messages.edit'),
    'filterDropdown' => false,
    'primaryAction' => '<a href="' . route('projectx.contacts.show', $contact->id) . '" class="btn btn-sm btn-light me-2">' . __('messages.view') . '</a>',
])

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="row g-7">
            @include('projectx::contacts.partials._groups_card', [
                'type' => $contact->type === 'both' ? 'customer' : $contact->type,
                'contacts' => $editContacts,
                'contactsCount' => $contactsCount ?? 0,
                'customerCount' => $customerCount ?? 0,
                'supplierCount' => $supplierCount ?? 0,
                'customer_groups' => $customer_groups ?? [],
            ])
            @include('projectx::contacts.partials._list_card', ['contacts' => $editContacts, 'contact' => $contact])
            <div class="col-xl-6">
                <div class="card card-flush h-lg-100" id="kt_contacts_main">
                    <div class="card-header pt-7">
                        <div class="card-title">
                            <h2>{{ __('messages.edit') }} {{ $contact->name }}</h2>
                        </div>
                    </div>
                    <div class="card-body pt-5">
                        <form action="{{ route('projectx.contacts.update', $contact->id) }}" method="post" id="projectx_contact_form">
                            @include('projectx::contacts.partials._form', [
                                'contact' => $contact,
                                'types' => $types,
                                'customer_groups' => $customer_groups,
                                'opening_balance' => $opening_balance ?? '0',
                            ])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
document.getElementById('projectx_contact_form')?.addEventListener('submit', function() {
    var btn = this.querySelector('button[type="submit"]');
    if (btn) {
        btn.setAttribute('data-kt-indicator', 'on');
        btn.classList.add('loading');
    }
});
</script>
@endsection
