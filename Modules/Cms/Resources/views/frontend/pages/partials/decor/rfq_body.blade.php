<section class="top-space-margin border-top border-color-extra-medium-gray pt-20px pb-20px ps-45px pe-45px lg-ps-35px lg-pe-35px md-ps-15px md-pe-15px sm-ps-0 sm-pe-0">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-12 breadcrumb breadcrumb-style-01 fs-14 alt-font">
                <ul>
                    <li><a href="{{ route('cms.home') }}">Home</a></li>
                    <li><a href="{{ route('cms.store.shop') }}">Shop</a></li>
                    <li><a href="{{ route('cms.store.product.show', ['id' => $detail['id']]) }}">{{ $detail['title'] }}</a></li>
                    <li>{{ __('cms::lang.storefront_request_quote') }}</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="pt-40px pb-40px">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-radius-6px p-30px">
                    <h4 class="alt-font text-dark-gray fw-700 mb-20px">{{ __('cms::lang.storefront_request_quote') }}</h4>
                    <p class="text-dark-gray mb-25px">
                        {{ __('cms::lang.storefront_request_quote_for', ['product' => $detail['title']]) }}
                    </p>

                    <form method="POST" action="{{ route('cms.store.rfq.store', ['id' => $detail['id']]) }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-20px">
                                <label class="form-label">{{ __('cms::lang.email') }}*</label>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    required
                                >
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-20px">
                                <label class="form-label">{{ __('cms::lang.storefront_phone') }}*</label>
                                <input
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    required
                                >
                                @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-20px">
                            <label class="form-label">{{ __('cms::lang.storefront_company') }}</label>
                            <input
                                type="text"
                                name="company"
                                value="{{ old('company') }}"
                                class="form-control @error('company') is-invalid @enderror"
                            >
                            @error('company')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-25px">
                            <label class="form-label">{{ __('cms::lang.storefront_note') }}</label>
                            <textarea
                                name="message"
                                rows="5"
                                class="form-control @error('message') is-invalid @enderror"
                            >{{ old('message') }}</textarea>
                            @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-base-color btn-medium btn-round-edge">
                            {{ __('cms::lang.storefront_submit_rfq') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
