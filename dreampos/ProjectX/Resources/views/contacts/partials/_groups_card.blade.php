{{-- Groups card: matches html/apps/contacts/getting-started.html --}}
<div class="col-lg-6 col-xl-3">
    <div class="card card-flush">
        <div class="card-header pt-7" id="kt_chat_contacts_header">
            <div class="card-title">
                <h2>Groups</h2>
            </div>
        </div>
        <div class="card-body pt-5">
            <div class="d-flex flex-column gap-5">
                <div class="d-flex flex-stack">
                    <a href="{{ route('projectx.contacts.index') }}" class="fs-6 fw-bold text-gray-800 text-hover-primary {{ !request('type') ? 'text-active-primary active' : '' }}">{{ __('contact.contacts') }}</a>
                    <div class="badge badge-light-primary">{{ $contactsCount ?? 0 }}</div>
                </div>
                <div class="d-flex flex-stack">
                    <a href="{{ route('projectx.contacts.index', ['type' => 'customer']) }}" class="fs-6 fw-bold text-gray-800 text-hover-primary {{ ($type ?? '') === 'customer' ? 'text-active-primary active' : '' }}">{{ __('report.customers') }}</a>
                    <div class="badge badge-light-primary">{{ $customerCount ?? 0 }}</div>
                </div>
                <div class="d-flex flex-stack">
                    <a href="{{ route('projectx.contacts.index', ['type' => 'supplier']) }}" class="fs-6 fw-bold text-gray-800 text-hover-primary {{ ($type ?? '') === 'supplier' ? 'text-active-primary active' : '' }}">{{ __('report.suppliers') }}</a>
                    <div class="badge badge-light-primary">{{ $supplierCount ?? 0 }}</div>
                </div>
                @if(isset($customer_groups) && $customer_groups && count($customer_groups) > 1)
                    @foreach($customer_groups as $cgId => $cgName)
                        @if($cgId !== '')
                            <div class="d-flex flex-stack">
                                <a href="{{ route('projectx.contacts.index', ['type' => 'customer', 'customer_group_id' => $cgId]) }}" class="fs-6 fw-bold text-gray-800 text-hover-primary">{{ $cgName }}</a>
                                <div class="badge badge-light-primary">-</div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
            <div class="separator my-7"></div>
            @if(auth()->user()->can('customer.view'))
                <label class="fs-6 fw-semibold form-label">{{ __('lang_v1.customer_group') }}</label>
                <div class="mb-3">
                    <a href="{{ route('customer-group.index') }}" class="btn btn-sm btn-light-primary w-100">{{ __('lang_v1.customer_group') }}</a>
                </div>
            @endif
            <div class="separator my-7"></div>
            @if(auth()->user()->can('supplier.create') || auth()->user()->can('customer.create'))
                <a href="{{ route('projectx.contacts.create') }}?type={{ $type ?? 'customer' }}" class="btn btn-primary w-100">
                    <i class="ki-duotone ki-badge fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>{{ __('contact.add_contact') }}</a>
            @endif
        </div>
    </div>
</div>
