@extends('projectx::layouts.main')

@section('title', __('contact.add_contact'))

@section('content')
@include('projectx::contacts.partials._toolbar', [
    'title' => __('contact.add_contact'),
    'breadcrumb' => __('contact.add_contact'),
    'filterDropdown' => false,
    'primaryAction' => '<a href="' . route('projectx.contacts.index') . '" class="btn btn-sm btn-light me-2">' . __('messages.cancel') . '</a>',
])

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="row g-7">
            @include('projectx::contacts.partials._groups_card', [
                'type' => $selected_type ?? 'customer',
                'contacts' => [],
                'contactsCount' => 0,
                'customerCount' => 0,
                'supplierCount' => 0,
                'customer_groups' => $customer_groups ?? [],
            ])
            <div class="col-lg-6 col-xl-3">
                <div class="card card-flush">
                    <div class="card-body pt-5">
                        <p class="text-gray-600 fs-7">{{ __('contact.manage_your_contact', ['contacts' => __('contact.contacts')]) }}</p>
                        <a href="{{ route('projectx.contacts.index') }}" class="btn btn-light w-100">{{ __('contact.contacts') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-flush h-lg-100" id="kt_contacts_main">
                    <div class="card-header pt-7">
                        <div class="card-title">
                            <h2>{{ __('contact.add_contact') }}</h2>
                        </div>
                    </div>
                    <div class="card-body pt-5">
                        <form action="{{ route('projectx.contacts.store') }}" method="post" id="projectx_contact_form">
                            @include('projectx::contacts.partials._form', [
                                'types' => $types,
                                'customer_groups' => $customer_groups,
                                'selected_type' => $selected_type,
                                'opening_balance' => '0',
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
