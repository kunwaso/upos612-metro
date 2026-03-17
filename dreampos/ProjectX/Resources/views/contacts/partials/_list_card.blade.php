{{-- Contacts list card: matches html/apps/contacts/getting-started.html --}}
<div class="col-lg-6 col-xl-3">
    <div class="card card-flush" id="kt_contacts_list">
        <div class="card-header pt-7" id="kt_contacts_list_header">
            <form class="d-flex align-items-center position-relative w-100 m-0" autocomplete="off" id="contacts_search_form" role="search">
                <i class="ki-duotone ki-magnifier fs-3 text-gray-500 position-absolute top-50 ms-5 translate-middle-y">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <input type="text" class="form-control form-control-solid ps-13" name="search" value="{{ request('search') }}" placeholder="Search by name, phone or email" id="contacts_search_input" aria-label="Search contacts by name, phone or email" />
            </form>
        </div>
        <div class="card-body pt-5" id="kt_contacts_list_body">
            <div class="scroll-y me-n5 pe-5 h-300px h-xl-auto" id="contacts_list_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_contacts_list_header" data-kt-scroll-wrappers="#kt_content, #kt_contacts_list_body" data-kt-scroll-offset="5px">
                @forelse($contacts ?? [] as $c)
                    @php
                        $searchableContactText = strtolower(implode(' ', array_filter([
                            $c->name ?? '',
                            $c->supplier_business_name ?? '',
                            $c->email ?? '',
                            $c->mobile ?? '',
                            $c->landline ?? '',
                            $c->alternate_number ?? '',
                            $c->contact_id ?? '',
                        ], static fn ($value) => $value !== null && $value !== '')));
                    @endphp
                    <div data-contact-item="1" data-searchable="{{ $searchableContactText }}">
                        <div class="d-flex flex-stack py-4">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px symbol-circle">
                                <span class="symbol-label bg-light-primary text-primary fs-6 fw-bolder">{{ strtoupper(substr($c->name ?? '?', 0, 1)) }}</span>
                            </div>
                                <div class="ms-4">
                                <a href="{{ route('projectx.contacts.show', $c->id) }}" class="fs-6 fw-bold text-gray-900 text-hover-primary mb-2 {{ (isset($contact) && $contact->id == $c->id) ? 'text-active-primary' : '' }}">{{ $c->name }}</a>
                                <div class="fw-semibold fs-7 text-muted">{{ $c->email ?: $c->mobile ?: '—' }}</div>
                            </div>
                            </div>
                        </div>
                        <div class="separator separator-dashed d-none"></div>
                    </div>
                @empty
                    <div class="text-muted text-center py-10 fs-7">{{ __('messages.no_data') }}</div>
                @endforelse
                <div class="text-muted text-center py-10 fs-7 d-none" id="contacts_list_no_results">{{ __('projectx::lang.no_matches') }}</div>
            </div>
        </div>
    </div>
</div>
