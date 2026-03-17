@extends('projectx::layouts.main')

@section('title', __('contact.contacts'))

@section('content')
@include('projectx::contacts.partials._toolbar', [
    'title' => __('contact.contacts'),
    'breadcrumb' => null,
    'filterDropdown' => true,
    'type' => $type,
    'primaryAction' => (auth()->user()->can('supplier.create') || auth()->user()->can('customer.create'))
        ? '<a href="' . route('projectx.contacts.create', ['type' => $type]) . '" class="btn btn-sm btn-primary" id="kt_toolbar_primary_button">' . __('contact.add_contact') . '</a>'
        : null,
])

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="row g-7">
            @include('projectx::contacts.partials._groups_card', [
                'type' => $type,
                'contacts' => $contacts,
                'contactsCount' => $contactsCount ?? 0,
                'customerCount' => $customerCount ?? 0,
                'supplierCount' => $supplierCount ?? 0,
                'customer_groups' => $customer_groups ?? [],
            ])
            @include('projectx::contacts.partials._list_card', ['contacts' => $contacts])

            <div class="col-xl-6">
                <div class="card card-flush h-lg-100" id="kt_contacts_main">
                    <div class="card-body p-0">
                        <div class="card-px text-center py-20 my-10">
                            <h2 class="fs-2x fw-bold mb-10">{{ __('contact.contacts') }}</h2>
                            <p class="text-gray-500 fs-4 fw-semibold mb-10">
                                {{ __('contact.manage_your_contact', ['contacts' => __('contact.contacts')]) }}
                                <br />Select a contact from the list or add a new one.
                            </p>
                            @if(auth()->user()->can('supplier.create') || auth()->user()->can('customer.create'))
                                <a href="{{ route('projectx.contacts.create', ['type' => $type]) }}" class="btn btn-primary">{{ __('contact.add_contact') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function () {
    var indexUrl = '{{ route("projectx.contacts.index") }}';
    var filterApply = document.getElementById('filter_apply');
    var filterType = document.getElementById('filter_type');
    var searchForm = document.getElementById('contacts_search_form');
    var searchInput = document.getElementById('contacts_search_input');
    var listScroll = document.getElementById('contacts_list_scroll');
    var noResultsElement = document.getElementById('contacts_list_no_results');
    var contactItems = listScroll ? Array.prototype.slice.call(listScroll.querySelectorAll('[data-contact-item="1"]')) : [];

    var debounce = function (callback, waitMs) {
        var timeoutId;

        return function () {
            var args = arguments;
            clearTimeout(timeoutId);
            timeoutId = setTimeout(function () {
                callback.apply(null, args);
            }, waitMs);
        };
    };

    var applyLiveFilter = function () {
        if (!searchInput) {
            return;
        }

        var searchTerm = (searchInput.value || '').trim().toLowerCase();
        var visibleItems = 0;

        contactItems.forEach(function (item) {
            var searchableText = (item.getAttribute('data-searchable') || '').toLowerCase();
            var shouldShow = searchTerm === '' || searchableText.indexOf(searchTerm) !== -1;

            item.classList.toggle('d-none', !shouldShow);
            if (shouldShow) {
                visibleItems += 1;
            }
        });

        if (noResultsElement) {
            var showNoResults = searchTerm !== '' && contactItems.length > 0 && visibleItems === 0;
            noResultsElement.classList.toggle('d-none', !showNoResults);
        }
    };

    if (searchForm) {
        searchForm.addEventListener('submit', function (event) {
            event.preventDefault();
        });
    }

    if (searchInput) {
        var searchFromUrl = new URLSearchParams(window.location.search).get('search');
        if (searchFromUrl !== null && searchInput.value !== searchFromUrl) {
            searchInput.value = searchFromUrl;
        }

        searchInput.addEventListener('input', debounce(applyLiveFilter, 180));
    }

    if (filterApply) {
        filterApply.addEventListener('click', function (event) {
            event.preventDefault();

            var params = new URLSearchParams();
            var selectedType = filterType ? filterType.value : '';
            var searchValue = searchInput ? searchInput.value.trim() : '';

            if (selectedType) {
                params.set('type', selectedType);
            }

            if (searchValue) {
                params.set('search', searchValue);
            }

            var queryString = params.toString();
            window.location.href = queryString ? (indexUrl + '?' + queryString) : indexUrl;
        });
    }

    applyLiveFilter();
})();
</script>
@endsection
