<div class="col-12">
    <div class="d-flex flex-wrap flex-stack pt-10 pb-8">
        <h3 class="fw-bold my-2">{{ __('product.product_users') }}
            <span class="fs-6 text-gray-500 fw-semibold ms-1">{{ __('product.active') }}</span>
        </h3>

        <div class="d-flex flex-wrap my-1">
            <div class="d-flex align-items-center position-relative my-1 me-4">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3"><span class="path1"></span><span class="path2"></span></i>
                <input type="text" id="kt_filter_search" class="form-control form-control-sm form-control-solid w-150px ps-10" placeholder="{{ __('product.search') }}..." />
            </div>

            <ul class="nav nav-pills me-5">
                <li class="nav-item m-0">
                    <a class="btn btn-sm btn-icon btn-light btn-color-muted btn-active-primary active me-3" data-bs-toggle="tab" href="#kt_product_users_card_pane">
                        <i class="ki-duotone ki-element-plus fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                    </a>
                </li>
                <li class="nav-item m-0">
                    <a class="btn btn-sm btn-icon btn-light btn-color-muted btn-active-primary" data-bs-toggle="tab" href="#kt_product_users_table_pane">
                        <i class="ki-duotone ki-row-horizontal fs-2"><span class="path1"></span><span class="path2"></span></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content">
        <div id="kt_product_users_card_pane" class="tab-pane fade show active">
            <div class="row g-6 g-xl-9">
                @php
                    $users = collect($productContactUsers ?? [])->values()->all();
                @endphp

                @foreach($users as $user)
                <div class="col-md-6 col-xxl-4">
                    <div class="card">
                        <div class="card-body d-flex flex-center flex-column pt-12 p-9">
                            <div class="symbol symbol-65px symbol-circle mb-5">
                                <img src="{{ asset('assets/media/avatars/' . $user['avatar']) }}" alt="image" />
                                @if($user['online'])
                                <div class="bg-success position-absolute border border-4 border-body h-15px w-15px rounded-circle translate-middle start-100 top-100 ms-n3 mt-n3"></div>
                                @endif
                            </div>
                            <a href="{{ !empty($user['id']) ? route('contacts.show', ['contact' => $user['id']]) : '#' }}" class="fs-4 text-gray-800 text-hover-primary fw-bold mb-0">{{ $user['name'] }}</a>
                            <div class="fw-semibold text-gray-500 mb-6">{{ $user['position'] }}, {{ $user['company'] }}</div>
                            <div class="d-flex flex-center flex-wrap">
                                <div class="border border-gray-300 border-dashed rounded min-w-80px py-3 px-4 mx-2 mb-3">
                                    <div class="fs-6 fw-bold text-gray-700">@format_currency((float) ($user['earnings'] ?? 0))</div>
                                    <div class="fw-semibold text-gray-500">{{ __('product.earnings') }}</div>
                                </div>
                                <div class="border border-gray-300 border-dashed rounded min-w-80px py-3 px-4 mx-2 mb-3">
                                    <div class="fs-6 fw-bold text-gray-700">{{ $user['tasks'] }}</div>
                                    <div class="fw-semibold text-gray-500">{{ __('product.tasks') }}</div>
                                </div>
                                <div class="border border-gray-300 border-dashed rounded min-w-80px py-3 px-4 mx-2 mb-3">
                                    <div class="fs-6 fw-bold text-gray-700">@format_currency((float) ($user['sales'] ?? 0))</div>
                                    <div class="fw-semibold text-gray-500">{{ __('product.sales') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div id="kt_product_users_table_pane" class="tab-pane fade">
            <div class="card card-flush">
                <div class="card-body pt-0">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold" id="kt_product_users_table">
                        <thead class="fs-7 text-gray-500 text-uppercase">
                            <tr>
                                <th class="min-w-250px">{{ __('product.fm_users') }}</th>
                                <th class="min-w-150px">Role</th>
                                <th class="min-w-90px">Earnings</th>
                                <th class="min-w-90px">Tasks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px symbol-circle me-3">
                                            <img alt="Pic" src="{{ asset('assets/media/avatars/' . $user['avatar']) }}" />
                                        </div>
                                        <div class="d-flex flex-column">
                                            <a href="{{ !empty($user['id']) ? route('contacts.show', ['contact' => $user['id']]) : '#' }}" class="text-gray-800 text-hover-primary fw-bold">{{ $user['name'] }}</a>
                                            <span class="text-gray-500 fw-semibold">{{ $user['company'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user['position'] }}</td>
                                <td>@format_currency((float) ($user['earnings'] ?? 0))</td>
                                <td>{{ $user['tasks'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
